<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use DB;
use App\Lib\Response as CustomResponse;
use Carbon\Carbon;
use App\Models\StockClosing;
use App\Models\StockProduct;
use App\Models\Product;

class StockClosingController extends Controller
{
	use CustomResponse;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
    }
	
	public function products(Request $request)
    {
		try{
			// Auto-add columns if missing
			if (!\Schema::hasColumn('stock_closings', 'remark')) {
				\Schema::table('stock_closings', function($t) { $t->string('remark')->nullable()->default('')->after('stock'); });
			}
			if (!\Schema::hasColumn('stock_closings', 'is_reviewed')) {
				\Schema::table('stock_closings', function($t) { $t->boolean('is_reviewed')->default(false)->after('remark'); });
			}
			if (!\Schema::hasColumn('stock_closings', 'supplier_invoice_id')) {
				\Schema::table('stock_closings', function($t) { $t->unsignedBigInteger('supplier_invoice_id')->default(0)->after('product_id'); });
			}
			$rules = [
                'date' => 'required',
            ];

			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
			$date = $request->date;

			// Per-row breakdown: one entry per (product_id, supplier_invoice_id).
			// "supplier_invoice_id" = invoice_id for stock_added/supplier_return/dump,
			// or supplier_invoice_id field for stock_consumed/customer_return.
			$srcInvoiceExpr = "(CASE WHEN event IN ('stock_added','stock_updated','supplier_return','dump') THEN invoice_id ELSE supplier_invoice_id END)";

			$stockData = StockProduct::where('is_archived', 0)
				->select(
					'product_id',
					DB::raw("$srcInvoiceExpr as src_supplier_invoice_id"),
					DB::raw("SUM(CASE WHEN event IN ('stock_added','stock_updated') THEN stock ELSE 0 END) as total_in"),
					DB::raw("SUM(CASE WHEN event IN ('stock_consumed','supplier_return','dump') THEN ABS(stock) ELSE 0 END) as total_out"),
					DB::raw("SUM(CASE WHEN event IN ('customer_return') THEN ABS(stock) ELSE 0 END) as total_crtn")
				)
				->groupBy('product_id', DB::raw("$srcInvoiceExpr"))
				->get();

			// Lookup invoice → supplier
			$invoiceIds = $stockData->pluck('src_supplier_invoice_id')->filter()->unique()->values();
			$supplierInvoices = \App\Models\SupplierInvoice::whereIn('id', $invoiceIds)
				->with('supplier:id,name')
				->get()
				->keyBy('id');

			// Existing closing entries for this date keyed by product+invoice
			$existingClosing = StockClosing::whereDate('created_at', $date)
				->get()
				->keyBy(fn($sc) => $sc->product_id . '|' . ((int)($sc->supplier_invoice_id ?? 0)));

			$products = Product::where('is_active', 1)->get()->keyBy('id');

			$results = [];
			foreach ($stockData as $s) {
				$productId = (int)$s->product_id;
				$invId     = (int)($s->src_supplier_invoice_id ?: 0);
				$product   = $products[$productId] ?? null;
				if (!$product) continue;

				$invModel = $supplierInvoices[$invId] ?? null;
				$supplierName = $invModel && $invModel->supplier ? $invModel->supplier->name : '-';
				$supplierId   = $invModel ? (int)$invModel->supplier_id : 0;
				$invoiceDate  = $invModel ? \Carbon\Carbon::parse($invModel->created_at)->format('Y-m-d') : null;

				$totalIn   = (float)$s->total_in;
				$totalOut  = (float)$s->total_out;
				$totalCrtn = (float)$s->total_crtn;
				$systemStock = max(0, $totalIn - $totalOut + $totalCrtn);

				$rowKey = $productId . '|' . $invId;
				$existing = $existingClosing[$rowKey] ?? null;

				$arr = $product->toArray();
				$arr['id']                  = $product->id;
				$arr['name']                = $product->name;
				$arr['supplier_invoice_id'] = $invId;
				$arr['supplier_id']         = $supplierId;
				$arr['supplier_name']       = $supplierName;
				$arr['invoice_date']        = $invoiceDate;
				$arr['total_in']            = $totalIn;
				$arr['total_out']           = $totalOut;
				$arr['system_stock']        = $systemStock;
				// Frontend reads `item.stock_closing.id` directly — return a single object,
				// not array-of-objects, so saved rows are recognized on reload.
				$arr['stock_closing']       = $existing ? [
					'id'    => $existing->id,
					'stock' => (int)$existing->stock,
					'remark'=> $existing->remark ?? '',
					'is_reviewed' => (bool)($existing->is_reviewed ?? false),
					'updated_at' => $existing->updated_at ? \Carbon\Carbon::parse($existing->updated_at)->format('d M Y, H:i') : null,
				] : null;
				$results[] = $arr;
			}

			// Sort by product name, then supplier name
			usort($results, function($a, $b){
				$n = strcmp($a['name'], $b['name']);
				if ($n !== 0) return $n;
				return strcmp($a['supplier_name'], $b['supplier_name']);
			});

			return $this->successResponse($results);

		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
    }
	
	public function stockManager()
    {
        return view('stock-manager.index');
    }

	public function crud()
    {
        return view('stock-closing.crud');
    }
	
	/**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        try{
			$rules = [
                'date' => 'required',
				'product_id' => ['required','integer'],
				'stock' => ['required','integer'],
            ];

			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }

			$supplierInvoiceId = (int)$request->input('supplier_invoice_id', 0);
			$hasInvCol = \Schema::hasColumn('stock_closings', 'supplier_invoice_id');

			$recordQuery = StockClosing::whereDate('created_at',$request->date)
				->where('product_id', $request->product_id);
			if ($hasInvCol) $recordQuery->where('supplier_invoice_id', $supplierInvoiceId);
			$record = $recordQuery->first();

			if(empty($record)){
				$obj = new StockClosing();
				$obj->created_at = $request->date . ' ' . now()->format('H:i:s');
				$obj->updated_at = now();
			}else{
				$obj = $record;
				$obj->updated_at = now();
			}
			$obj->product_id = $request->product_id;
			$obj->stock = $request->stock;
			if ($hasInvCol) $obj->supplier_invoice_id = $supplierInvoiceId;
			if ($request->has('remark')) $obj->remark = $request->remark;
			$obj->is_reviewed = true;
			$obj->save();
			
			return $this->successResponse($obj);
			
		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function saveAll(Request $request)
    {
		try{
			$rules = [
                'date' => 'required|date',
				'products' => 'required|array',
				'products.*.id' => 'required|integer',
				'products.*.stock' => 'required|numeric',
            ];
			
			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
			
			$date = $request->date; // 👈 from frontend
			$now = Carbon::now();
			
			$hasInvCol = \Schema::hasColumn('stock_closings', 'supplier_invoice_id');
			$out = "";
			foreach($request->products as $product){
				$invId = (int)($product['supplier_invoice_id'] ?? 0);
				$q = StockClosing::whereDate('created_at', $date)->where('product_id',$product['id']);
				if ($hasInvCol) $q->where('supplier_invoice_id', $invId);
				$record = $q->first();
				if(empty($record)){
					$new = new StockClosing();
					$new->created_at = $date . ' ' . now()->format('H:i:s');
					$new->updated_at = now();
					$new->product_id = $product['id'];
					$new->stock = $product['stock'];
					if ($hasInvCol) $new->supplier_invoice_id = $invId;
					$new->is_reviewed = true;
					$new->save();
				}else{
					$record->updated_at = now();
					$record->product_id = $product['id'];
					$record->stock = $product['stock'];
					if ($hasInvCol) $record->supplier_invoice_id = $invId;
					$record->is_reviewed = true;
					$record->save();
				}
			}
		
			return $this->successResponse([]);
			
		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
    }
	
	public function saveOne(Request $request)
    {
        try{
			$rules = [
                'date' => 'required',
				'product_id' => ['required','integer'],
				'stock' => ['required','integer'],
            ];

			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }

			$supplierInvoiceId = (int)$request->input('supplier_invoice_id', 0);
			$hasInvCol = \Schema::hasColumn('stock_closings', 'supplier_invoice_id');

			$recordQuery = StockClosing::whereDate('created_at',$request->date)
				->where('product_id', $request->product_id);
			if ($hasInvCol) $recordQuery->where('supplier_invoice_id', $supplierInvoiceId);
			$record = $recordQuery->first();

			if(empty($record)){
				$obj = new StockClosing();
				$obj->created_at = $request->date . ' ' . now()->format('H:i:s');
				$obj->updated_at = now();
			}else{
				$obj = $record;
				$obj->updated_at = now();
			}
			$obj->product_id = $request->product_id;
			$obj->stock = $request->stock;
			if ($hasInvCol) $obj->supplier_invoice_id = $supplierInvoiceId;
			if ($request->has('remark')) $obj->remark = $request->remark;
			$obj->is_reviewed = true;
			$obj->save();
			
			return $this->successResponse($obj);
			
		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function unassignedSuppliers()
    {
        return view('stock-closing.unassigned-suppliers');
    }

    public function unassignedSuppliersList(Request $request)
    {
        try {
            $query = \App\Models\CustomerInvoiceProduct::with(['product', 'customerInvoice.customer', 'supplier'])
                ->where('is_archive', 0)
                ->where(function($q) {
                    $q->whereNull('supplier_id')
                      ->orWhere('supplier_id', 0);
                });

            if ($request->search) {
                $query->whereHas('product', function($q) use ($request) {
                    $q->where('name', 'like', '%'.$request->search.'%');
                });
            }

            if ($request->from_date) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->to_date) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            $products = $query->orderBy('id', 'desc')->get()->map(function($item) {
                $date = '';
                if ($item->created_at) {
                    try { $date = \Carbon\Carbon::parse($item->created_at)->format('d/m/Y'); } catch(\Exception $e) {}
                }
                $customerName = '';
                if ($item->customerInvoice && $item->customerInvoice->customer) {
                    $customerName = $item->customerInvoice->customer->name ?? '';
                }
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product ? $item->product->name : 'Unknown',
                    'customer_name' => $customerName,
                    'invoice_id' => $item->customer_invoice_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'sub_total' => $item->sub_total,
                    'remarks' => $item->remarks,
                    'created_at' => $date,
                ];
            });

            // Total count (assigned + unassigned) with same date filters
            $totalQuery = \App\Models\CustomerInvoiceProduct::where('is_archive', 0);
            if ($request->from_date) $totalQuery->whereDate('created_at', '>=', $request->from_date);
            if ($request->to_date) $totalQuery->whereDate('created_at', '<=', $request->to_date);
            $totalCount = $totalQuery->count();

            return response()->json(['success' => true, 'payload' => $products, 'total' => $totalCount]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function suppliersByProduct($product_id)
    {
        try {
            $data = \App\Models\SupplierInvoiceProduct::getProductSuppliers($product_id);

            // Did any row actually surface an invoice with stock > 0?
            $hasUsableInvoice = false;
            foreach (($data ?? []) as $row) {
                if (!$row) continue;
                $supplier = is_object($row) ? ($row->supplier ?? null) : null;
                $invoices = $supplier ? ($supplier->invoices ?? null) : null;
                if ($invoices && count($invoices) > 0) { $hasUsableInvoice = true; break; }
            }

            // No exact-match supplier carries stock for this product (typical reason: customer
            // row says "Onion" but suppliers sell variants like "Onion-Spanish", "Onion Dutch").
            // Fall back to fuzzy product-name matching so the user still gets actionable options.
            $fuzzyUsed = false;
            $fuzzyMatches = [];
            $targetName = null;
            if (!$hasUsableInvoice) {
                $targetProduct = \App\Models\Product::find($product_id);
                $targetName = $targetProduct ? trim($targetProduct->name) : '';
                if ($targetName !== '') {
                    // Pull the first word (e.g. "Onion" from "Onion-Spanish") to widen the search
                    $rootToken = preg_split('/[\s\-\/\,]+/', $targetName)[0] ?? $targetName;
                    $rootToken = trim($rootToken);
                    if (mb_strlen($rootToken) >= 3) {
                        $similar = \App\Models\Product::where('id', '!=', $product_id)
                            ->where(function ($q) use ($rootToken, $targetName) {
                                $q->where('name', 'LIKE', $rootToken . '%')
                                  ->orWhere('name', 'LIKE', '%' . $rootToken . '%')
                                  ->orWhere('name', 'LIKE', $targetName . '%');
                            })
                            ->limit(50)
                            ->get(['id', 'name']);
                        foreach ($similar as $simProd) {
                            $simRows = \App\Models\SupplierInvoiceProduct::getProductSuppliers($simProd->id);
                            foreach (($simRows ?? []) as $row) {
                                if (!$row) continue;
                                $supplier = is_object($row) ? ($row->supplier ?? null) : null;
                                $invoices = $supplier ? ($supplier->invoices ?? null) : null;
                                if (!$invoices || count($invoices) === 0) continue;
                                // Tag each invoice option with the matched product name so the
                                // frontend can disambiguate ("Onion-Spanish" vs "Onion Dutch")
                                $tagged = collect($invoices)->map(function ($inv) use ($simProd) {
                                    $inv = is_object($inv) ? $inv : (object)$inv;
                                    $inv->matched_product_id = $simProd->id;
                                    $inv->matched_product_name = $simProd->name;
                                    $inv->invoice_title = ($inv->invoice_title ?? '') . ' [' . $simProd->name . ']';
                                    return $inv;
                                });
                                $clonedSupplier = clone $supplier;
                                $clonedSupplier->setRelation('invoices', $tagged);
                                $row->setRelation('supplier', $clonedSupplier);
                                $row->matched_product_id = $simProd->id;
                                $row->matched_product_name = $simProd->name;
                                $fuzzyMatches[] = $row;
                            }
                        }
                        if (count($fuzzyMatches) > 0) {
                            $fuzzyUsed = true;
                            $data = collect($fuzzyMatches);
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'payload' => $data,
                'fuzzy_match' => $fuzzyUsed,
                'requested_product_name' => $targetName,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function assignSupplier(Request $request, \App\Services\StockProducts $stockProducts)
    {
        try {
            $item = \App\Models\CustomerInvoiceProduct::findOrFail($request->id);
            $item->supplier_id = $request->supplier_id;
            $item->supplier_invoice_id = $request->supplier_invoice_id ?? null;
            $item->supplier_invoice_product_id = $request->supplier_invoice_product_id ?? null;
            $item->save();

            // Record stock deduction now that supplier is assigned
            if ($request->supplier_invoice_product_id) {
                $stockProducts->recordStock([
                    'supplier_invoice_product_id' => $request->supplier_invoice_product_id,
                    'supplier_invoice_id' => $request->supplier_invoice_id,
                    'customer_id' => $item->customer_id,
                    'product_id' => $item->product_id,
                    'stock' => $item->quantity,
                    'type' => 'customer',
                    'invoice_id' => $item->customer_invoice_id,
                    'event' => 'stock_consumed',
                    'price' => $item->unit_price,
                    'ref_id' => $item->id
                ]);
            }

            return response()->json(['success' => true, 'message' => 'Supplier assigned successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
