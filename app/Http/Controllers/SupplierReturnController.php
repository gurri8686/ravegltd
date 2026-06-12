<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Lib\Response as CustomResponse;
use App\Models\SupplierInvoiceProduct;
use App\Models\StockProduct;
use App\Models\SupplierCreditUsage;
use App\Events\SupplierReturnEvent;
use DB;
use Carbon\Carbon;

class SupplierReturnController extends Controller
{
	use CustomResponse;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('return.supplier');
    }

    public function history()
    {
        return view('return.supplier-history');
    }
	
	public function suppliers()
    {
        try {
            return $this->successResponse(\App\Models\Supplier::getActive());
        } catch (\Exception $ex) {
            return $this->exceptionResponse($ex);
        }
    }

    public function products()
    {
        return $this->successResponse(\App\Models\Product::getActive());
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
	
	/**
	 * Get all products purchased from a supplier (with available returnable qty)
	 */
	public function supplierProducts(Request $request){
		try{
			$query = SupplierInvoiceProduct::where('is_archive', 0)
				->with('product')
				->with('supplier')
				->with('invoice')
				->select('id','supplier_invoice_id','product_id','supplier_id','quantity','unit_price','sub_total','remarks','created_at');

			if ($request->filled('supplier_id')) {
				$query->where('supplier_id', $request->supplier_id);
			}
			if ($request->filled('from_date')) $query->whereDate('created_at', '>=', $request->from_date);
			if ($request->filled('to_date')) $query->whereDate('created_at', '<=', $request->to_date);

			$items = $query->orderBy('id','desc')->get();

			$data = $items->map(function($item) {
				// Supplier-side reductions (returns + dumps)
				$supplierUsed = StockProduct::where('type','supplier')
					->where('is_archived',0)
					->where('invoice_id', $item->supplier_invoice_id)
					->where('product_id', $item->product_id)
					->whereIn('event', ['supplier_return', 'dump'])
					->sum('stock');

				// Customer net consumption (sales - customer returns) linked to this invoice line
				$customerConsumed = StockProduct::where('is_archived', 0)
					->where('type', 'customer')
					->where('event', 'stock_consumed')
					->where('supplier_invoice_product_id', $item->id)
					->where('supplier_invoice_id', $item->supplier_invoice_id)
					->sum('stock');

				$customerReturned = StockProduct::where('is_archived', 0)
					->where('type', 'customer')
					->where('event', 'customer_return')
					->where('supplier_invoice_product_id', $item->id)
					->where('supplier_invoice_id', $item->supplier_invoice_id)
					->sum('stock');

				$available = $item->quantity - abs($supplierUsed) - (abs($customerConsumed) - abs($customerReturned));

				$date = '';
				try { $date = uk_ts($item->getRawOriginal('created_at'), 'd M Y'); } catch(\Exception $e) {}
				$supplierId = $item->supplier_id ?: ($item->invoice ? $item->invoice->supplier_id : null);
				return [
					'id' => $item->id,
					'supplier_id' => $supplierId,
					'supplier_name' => $item->supplier ? $item->supplier->name : ($item->invoice && $item->invoice->supplier ? $item->invoice->supplier->name : 'N/A'),
					'product_id' => $item->product_id,
					'product_name' => $item->product ? $item->product->name : 'Unknown',
					'invoice_id' => $item->supplier_invoice_id,
					'quantity' => $item->quantity,
					'unit_price' => $item->unit_price,
					'remarks' => $item->remarks,
					'returned' => abs($supplierUsed),
					'available' => max(0, $available),
					'date' => $date,
				];
			})->filter(fn($i) => $i['available'] > 0)->values();

			return $this->successResponse($data);
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
			//print_r($stocks);
			//exit;
			
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
				->where('event','supplier_return')
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
					'quantity' => abs($return->stock),
					'price' => abs($return->price),
					'invoice_id' => $return->invoice_id,
					'note' => $return->remarks ?? '',
					'supplier_id' => $supplier->id ?? '',
					'date' => $date,
					'invoices' => '',
					'total' => abs($return->stock) * abs($return->price),
					'supplier' => $supplier->name ?? '',
				];
			}

			return $this->successResponse($data);

		}catch(\Exception $ex){
			\Log::error('SupplierReturn returns error: '.$ex->getMessage().' at '.$ex->getFile().':'.$ex->getLine());
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
		$productId = is_array($request->product_id) ? $request->product_id['value'] : $request->product_id;
		DB::beginTransaction();
		try{
			// Direct stock check
			$baseQuery = StockProduct::where('product_id', $productId)
				->where('invoice_id', $request->invoice_id['invoice_id'])
				->where('is_archived', 0)
				->where('type', 'supplier');

			$added = (clone $baseQuery)->where('ref_id', $request->invoice_id['ref_id'])
				->where('event', 'stock_added')->sum('stock');

			if ($added == 0) {
				$added = (clone $baseQuery)->where('event', 'stock_added')->sum('stock');
				$returned = (clone $baseQuery)->where('event', 'supplier_return')->sum('stock');
			} else {
				$returned = (clone $baseQuery)->where('ref_id', $request->invoice_id['ref_id'])
					->where('event', 'supplier_return')->sum('stock');
			}

			$netStock = abs($added) - abs($returned);

			if($netStock <= 0){
				throw new \Exception("No stock available to return.");
			}
			if($request->quantity <= 0){
				throw new \Exception("Min 1 quantity is required.");
			}
			if($request->quantity > $netStock){
				throw new \Exception("Max quantity ".$netStock. " is allowed to return.");
			}

			$results = event(new SupplierReturnEvent([
				'supplier_id' => $request->supplier_id,
				'product_id' => $productId,
				'supplier_invoice_id' => $request->invoice_id['invoice_id'],
				'supplier_invoice_product_id' => $request->invoice_id['id'],
				'quantity' => $request->quantity,
				'price' => $request->price,
				'remarks' => $request->note,
				'type' => 'supplier',
				'event' => 'supplier_return',
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
				'event' => 'supplier_return',
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
				'event' => 'supplier_return',
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

    public function creditBalance(Request $request, $supplier_id)
    {
        try {
            $tableExists = \Illuminate\Support\Facades\Schema::hasTable('supplier_credit_usages');
            $earned    = SupplierCreditUsage::totalEarned($supplier_id);
            $used      = $tableExists ? SupplierCreditUsage::totalUsed($supplier_id) : 0;
            $available = max(0, round($earned - $used, 2));
            return $this->successResponse([
                'total_earned' => round($earned, 2),
                'total_used'   => round($used, 2),
                'available'    => $available,
            ]);
        } catch (\Exception $ex) {
            return $this->exceptionResponse($ex);
        }
    }

    public function creditBalanceAll()
    {
        try {
            $earned = StockProduct::where('event', 'supplier_return')
                ->where('is_archived', 0)
                ->where('type', 'supplier')
                ->get()
                ->sum(fn($r) => abs($r->stock) * $r->price);
            $used = 0;
            if (\Illuminate\Support\Facades\Schema::hasTable('supplier_credit_usages')) {
                $used = SupplierCreditUsage::sum('amount');
            }
            $available = max(0, round($earned - $used, 2));
            return $this->successResponse([
                'total_earned' => round($earned, 2),
                'total_used'   => round($used, 2),
                'available'    => $available,
            ]);
        } catch (\Exception $ex) {
            return $this->exceptionResponse($ex);
        }
    }
}
