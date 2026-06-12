<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Lib\Response as CustomResponse;
use App\Models\SupplierInvoiceProduct;
use App\Models\StockProduct;
use App\Events\SupplierReturnEvent;
use DB;


class DumpController extends Controller
{
	use CustomResponse;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('return.dump');
    }

    public function history()
    {
        return view('return.dump-history');
    }

    public function suppliers()
    {
        return $this->successResponse(\App\Models\Supplier::getActive());
    }

    public function products()
    {
        return $this->successResponse(\App\Models\Product::getActive());
    }

    public function supplierProducts(Request $request)
    {
        try {
            $supplierId = $request->supplier_id;

            $fromDate = $request->from_date ?? null;
            $toDate   = $request->to_date   ?? null;

            // Get per-invoice products from this supplier (filtered by date if provided)
            $query = \App\Models\SupplierInvoiceProduct::where('is_archive', 0)
                ->with('product')
                ->with('supplier')
                ->with('invoice')
                ->orderBy('id', 'desc');

            if ($supplierId) $query->where('supplier_id', $supplierId);
            if ($fromDate) $query->whereDate('created_at', '>=', $fromDate);
            if ($toDate)   $query->whereDate('created_at', '<=', $toDate);

            $items = $query->get();

            $result = [];
            foreach ($items as $item) {
                if (!$item->product) continue;

                $itemSupplierId = $item->supplier_id ?: ($item->invoice ? $item->invoice->supplier_id : null);
                // Stock added by supplier for this invoice-product line
                $added = \App\Models\StockProduct::where('product_id', $item->product_id)
                    ->where('supplier_id', $itemSupplierId)
                    ->where('invoice_id', $item->supplier_invoice_id)
                    ->where('ref_id', $item->id)
                    ->where('is_archived', 0)
                    ->where('type', 'supplier')
                    ->where('event', 'stock_added')
                    ->sum('stock');

                if ($added == 0) {
                    // fallback without ref_id
                    $added = \App\Models\StockProduct::where('product_id', $item->product_id)
                        ->where('supplier_id', $itemSupplierId)
                        ->where('invoice_id', $item->supplier_invoice_id)
                        ->where('is_archived', 0)
                        ->where('type', 'supplier')
                        ->where('event', 'stock_added')
                        ->sum('stock');
                }

                if ($added == 0) continue;

                // Supplier-side reductions (returns + dumps)
                $supplierUsed = \App\Models\StockProduct::where('product_id', $item->product_id)
                    ->where('supplier_id', $itemSupplierId)
                    ->where('invoice_id', $item->supplier_invoice_id)
                    ->where('is_archived', 0)
                    ->where('type', 'supplier')
                    ->whereIn('event', ['supplier_return', 'dump'])
                    ->sum('stock');

                // Customer-side net consumption (sales - customer returns) linked to this invoice line
                $customerConsumed = \App\Models\StockProduct::where('is_archived', 0)
                    ->where('type', 'customer')
                    ->where('event', 'stock_consumed')
                    ->where('supplier_invoice_product_id', $item->id)
                    ->where('supplier_invoice_id', $item->supplier_invoice_id)
                    ->sum('stock');

                $customerReturned = \App\Models\StockProduct::where('is_archived', 0)
                    ->where('type', 'customer')
                    ->where('event', 'customer_return')
                    ->where('supplier_invoice_product_id', $item->id)
                    ->where('supplier_invoice_id', $item->supplier_invoice_id)
                    ->sum('stock');

                $netStock = abs($added) - abs($supplierUsed) - (abs($customerConsumed) - abs($customerReturned));

                if ($netStock > 0) {
                    $itemDate = '';
                    try { $itemDate = uk_ts($item->getRawOriginal('created_at'), 'd M Y'); } catch(\Exception $e) {}
                    $result[] = [
                        'id'            => $item->id,
                        'supplier_id'   => $itemSupplierId,
                        'supplier_name' => $item->supplier ? $item->supplier->name : 'N/A',
                        'product_id'    => $item->product_id,
                        'product_name'  => $item->product->name,
                        'invoice_id'    => $item->supplier_invoice_id,
                        'quantity'      => $item->quantity,
                        'unit_price'    => $item->unit_price,
                        'remarks'       => $item->remarks,
                        'stock'         => $netStock,
                        'date'          => $itemDate,
                    ];
                }
            }

            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
	
	public function product(Request $request)
    {
		try{
			$rules = [
                'supplier_id' => 'required',
				'product_id.value' => 'required',
				'invoice_id.id' => 'required',
				'invoice_id.invoice_id' => 'required',
				'date' => 'required',
            ];
			
			$validator = Validator::make($request->all(), $rules);
			
			if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
			
			$stock = StockProduct::_productsWithInvoiceStockSupplier(
				$request->invoice_id['invoice_id'],
				$request->supplier_id,
				$request->product_id['value'],
				$request->invoice_id['id']
			);
			//print_r($stock); exit;
			if(empty($stock)){
				throw new \Exception('Stock not Found!');
			}
			
			if($stock['net_stock'] <= 0){
				throw new \Exception('Stock already used!');
			}
						
			$invoices = SupplierInvoiceProduct
			::select(['supplier_invoice_products.*'])
			->where('product_id', $request->product_id['value'])
			->where('supplier_id', $request->supplier_id)
			->where('is_archive', 0)
			->where('id', $request->invoice_id['id'])
			->where('supplier_invoice_id', $request->invoice_id['invoice_id'])
			//->unique() // remove duplicate IDs
			->first();
			
			$invoices->quantity = $stock['net_stock'];
			
			return $this->successResponse($invoices);
			
		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
    }
	
	public function invoices(Request $request){
		try{
			$rules = [
                'supplier_id' => 'required',
				'product_id' => 'required',
				'date' => 'required',
            ];
			
			$validator = Validator::make($request->all(), $rules);
			
			if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
			
			$stocks = StockProduct::getProductsWithInvoiceStockSupplier(
				$request->supplier_id,
				$request->product_id,
				$request->date, "",
				$request->end_date ?? ""
			); 
			return $this->successResponse($stocks);
			
			$invoices = SupplierInvoiceProduct::where('product_id', $request->product_id)
			->where('supplier_id', $request->supplier_id)
			->where('is_archive', 0)
			->whereDate('created_at', '>=', $request->date)
			->with('product')
			//->unique() // remove duplicate IDs
			->get();
			
			$data = [];
			foreach($invoices as $i){
				$data[] = [
					'label' => $i->supplier_invoice_id.':('.$i->quantity.'):'.$i->remarks,
					'invoice_id' => $i->supplier_invoice_id,
					'id' => $i->id,
				];
			}
			return $this->successResponse($data);
			
		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
	}
	
	public function returns(Request $request){
		try{
			$query = StockProduct
				::where('is_archived',0)
				->where('event','dump')
				->where('type','supplier')
				->with('product')
				->with('supplier');

			if ($request->filled('date')) {
				$query->whereDate('created_at', '>=', $request->date);
			}
			if ($request->filled('end_date')) {
				$query->whereDate('created_at', '<=', $request->end_date);
			}
			if ($request->filled('supplier_id')) {
				$query->where('supplier_id', $request->supplier_id);
			}

			$returns = $query->get();

			$data = [];
			foreach($returns as $return){
				$supplier = optional($return->supplier);
				$date = '';
				try { $date = uk_ts($return->getRawOriginal('created_at'), 'Y-m-d H:i:s'); } catch(\Exception $e) {}
				$data[] = [
					'id' => $return->id,
					'editable' => false,
					'product_id' => optional($return->product)->name ?? '',
					'quantity' => $return->stock,
					'price' => $return->price,
					'invoice_id' => $return->invoice_id,
					'supplier_id' => $supplier->id ?? '',
					'date' => $date,
					'invoices' => '',
					'total' => $return->stock * $return->price,
					'supplier' => $supplier->name ?? '',
					'note' => $return->remarks ?? '',
				];
			}

			return $this->successResponse($data);

		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
	}
	
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function returnCreate(Request $request)
    {
		//print_r($request->all()); exit;
		// validation later.
		DB::beginTransaction();
		try{
			$productId = $request->product_id['value'];
			$invoiceId = $request->invoice_id['invoice_id'];
			$refId    = $request->invoice_id['id'];

			$baseQuery = StockProduct::where('product_id', $productId)
				->where('invoice_id', $invoiceId)
				->where('ref_id', $refId)
				->where('is_archived', 0)
				->where('type', 'supplier');

			$added   = (clone $baseQuery)->where('event', 'stock_added')->sum('stock');
			$dumped  = (clone $baseQuery)->where('event', 'dump')->sum('stock');
			$netStock = abs($added) - abs($dumped);

			if($added == 0){
				throw new \Exception("Invalid Invoice Stock!");
			}
			if($request->quantity <= 0){
				throw new \Exception("Min 1 quantity is required.");
			}
			if($netStock <= 0){
				throw new \Exception("Max quantity already dumped.");
			}
			if($request->quantity > $netStock){
				throw new \Exception("Max quantity ".$netStock. " is allowed to dump.");
			}
			//print_r($stock); exit;
			
			$results = event(new SupplierReturnEvent([
				'supplier_id' => !empty($request->supplier_id) ? $request->supplier_id : null,
				'product_id' => $request->product_id['value'],
				'supplier_invoice_id' => $request->invoice_id['invoice_id'],
				'supplier_invoice_product_id' => $request->invoice_id['id'],
				'quantity' => $request->quantity,
				'price' => $request->price,
				'remarks' => $request->note,
				'type' => 'supplier',
				'event' => 'dump',
			]));
			DB::commit();
			return $this->successResponse($results);
		}catch(\Exception $ex){
			DB::rollback();
			return $this->exceptionResponse($ex);
		}
    }
	
	public function returnUpdate(Request $request)
    {
        // validation later.
		DB::beginTransaction();
		try{
			$current = StockProduct::where('id', $request->id)->where('invoice_id',$request->invoice_id)
				->where('supplier_id', $request->supplier_id)->where('is_archived', 0)->first();
			
			if(empty($current)){
				throw new \Exception("Invalid return requested to update.");
			}
			
			$stock = StockProduct::_productsWithInvoiceStockSupplier(
				$request->invoice_id,
				$request->supplier_id,
				$current->product_id,
				$current->ref_id,[$request->id]
			);
			
			//print_r($stock); exit;
			
			if(empty($stock)){
				throw new \Exception("Invalid return requested to update.");
			}
			
			if($stock['net_stock'] <= 0){
				throw new \Exception("Full Stock already used or returned.");
			}
			
			if($request->quantity > $stock['net_stock']){
				throw new \Exception("Max quantity ".$stock['net_stock']. " is allowed to return.");
			}
			
			//print_r($stock); exit;
			
			$results = event(new SupplierReturnEvent([
				'id' => $request->id,
				'supplier_id' => $request->supplier_id,
				//'product_id' => $request->product_id['value'],
				'supplier_invoice_id' => $request->invoice_id,
				//'customer_invoice_product_id' => $request->invoice_id['id'],
				'quantity' => $request->quantity,
				'price' => $request->price,
				//'remarks' => $request->note,
				//'type' => 'customer',
				'event' => 'dump',
			]));
			DB::commit();
			return $this->successResponse($results);
		}catch(\Exception $ex){
			DB::rollback();
			return $this->exceptionResponse($ex);
		}
    }
	
	public function returnDelete(Request $request)
    {
        // validation later.
		DB::beginTransaction();
		try{
			$results = event(new SupplierReturnEvent([
				'id' => $request->id,
				'delete' => 1,
				'supplier_id' => $request->supplier_id,
				'supplier_invoice_id' => $request->invoice_id,
				'quantity' => $request->quantity,
				'price' => $request->price,
				'event' => 'dump',
			]));
			DB::commit();
			return $this->successResponse($results);
		}catch(\Exception $ex){
			DB::rollback();
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
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
}
