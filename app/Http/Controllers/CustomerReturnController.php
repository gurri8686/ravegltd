<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Lib\Response as CustomResponse;
use App\Models\CustomerInvoiceProduct;
use App\Models\StockProduct;
use App\Models\CustomerCreditUsage;
use App\Events\CustomerReturnEvent;
use DB;

class CustomerReturnController extends Controller
{
	use CustomResponse;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('return.customer');
    }

    public function history()
    {
        return view('return.customer-history');
    }
	
	public function customers()
    {
        return $this->successResponse(\App\Models\Customer::getActive());
    }
	
	public function products()
    {
        return $this->successResponse(\App\Models\Product::getActive());
    }
	
	public function product(Request $request)
    {
		try{
			$rules = [
                'customer_id' => 'required',
				'product_id.value' => 'required',
				'invoice_id.id' => 'required',
				'invoice_id.invoice_id' => 'required',
            ];
			
			$validator = Validator::make($request->all(), $rules);
			
			if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
			
			$stock = StockProduct::_productsWithInvoiceStockCustomer(
				$request->invoice_id['invoice_id'],
				$request->customer_id,
				$request->product_id['value'],
				$request->invoice_id['id']
			);
			
			if(empty($stock)){
				throw new \Exception('Stock not Found!');
			}
			
			if($stock->net_stock <= 0){
				throw new \Exception('Stock already used!');
			}
			
			$invoices = CustomerInvoiceProduct
			::where('product_id', $request->product_id['value'])
			->where('customer_id', $request->customer_id)
			->where('is_archive', 0)
			->where('id', $request->invoice_id['id'])
			->where('customer_invoice_id', $request->invoice_id['invoice_id'])
			//->unique() // remove duplicate IDs
			->first();
			
			$invoices->quantity = $stock->net_stock;
			
			return $this->successResponse($invoices);
			
		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
    }
	
	/**
	 * Get all products purchased by a customer (grouped by product)
	 */
	public function customerProducts(Request $request){
		try{
			$query = CustomerInvoiceProduct::where('is_archive', 0)
				->with('product')
				->with('customer')
				->select('id','customer_id','customer_invoice_id','supplier_invoice_product_id','supplier_invoice_id','supplier_id','product_id','quantity','unit_price','sub_total','remarks','created_at');

			if ($request->filled('customer_id')) {
				$query->where('customer_id', $request->customer_id);
			}
			if ($request->filled('from_date')) $query->whereDate('created_at', '>=', $request->from_date);
			if ($request->filled('to_date')) $query->whereDate('created_at', '<=', $request->to_date);

			$items = $query->orderBy('id','desc')->get();

			// Check how much already returned per item
			$data = $items->map(function($item) {
				$returned = \App\Models\StockProduct::where('event','customer_return')
					->where('type','customer')
					->where('is_archived',0)
					->where('invoice_id', $item->customer_invoice_id)
					->where('product_id', $item->product_id)
					->where('ref_id', $item->id)
					->sum('stock');
				$available = $item->quantity - abs($returned);
				$date = '';
				try { $date = uk_ts($item->getRawOriginal('created_at'), 'd M Y'); } catch(\Exception $e) {}
				return [
					'id' => $item->id,
					'customer_id' => $item->customer_id,
					'customer_name' => $item->customer ? $item->customer->name : '',
					'product_id' => $item->product_id,
					'product_name' => $item->product ? $item->product->name : 'Unknown',
					'invoice_id' => $item->customer_invoice_id,
					'supplier_invoice_product_id' => $item->supplier_invoice_product_id,
					'supplier_invoice_id' => $item->supplier_invoice_id,
					'supplier_id' => $item->supplier_id,
					'quantity' => $item->quantity,
					'unit_price' => $item->unit_price,
					'remarks' => $item->remarks,
					'returned' => abs($returned),
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
                'customer_id' => 'required',
				'product_id' => 'required',
            ];

			$validator = Validator::make($request->all(), $rules);

			if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }

			$dateParam = $request->filled('date') ? $request->date : '2000-01-01';
			$stocks = StockProduct::getProductsWithInvoiceStockCustomer($request->customer_id, $request->product_id, $dateParam, "");
			
			//print_R($stocks->toArray()); exit;
			
			return $this->successResponse($stocks);
			
			/*$invoices = CustomerInvoiceProduct::where('product_id', $request->product_id)
			->where('customer_id', $request->customer_id)
			->where('is_archive', 0)
			->whereDate('created_at', '>=', $request->date)
			->with('product')
			//->unique() // remove duplicate IDs
			->get();
			
			$data = [];
			foreach($invoices as $i){
				$data[] = [
					'label' => $i->customer_invoice_id.':('.$i->quantity.'):'.$i->remarks,
					'invoice_id' => $i->customer_invoice_id,
					'id' => $i->id,
				];
			}
			return $this->successResponse($data);*/
			
		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
	}
	
	public function returns(Request $request){
		try{
			$query = StockProduct::where('event','customer_return')
				->where('is_archived',0)
				->where('type','customer');

			if($request->filled('date') && $request->date !== ''){
				$query->whereDate('created_at', '>=', $request->date);
			}
			if($request->filled('to_date') && $request->to_date !== ''){
				$query->whereDate('created_at', '<=', $request->to_date);
			}

			$query = $query
				->with('product')
				->with(['customerInvoice' => function($query){
					$query->with('customer');
				}]);
			
			if ($request->has('customer_id') && !empty($request->customer_id)) {
				$query->whereHas('customerInvoice.customer', function ($q) use ($request) {
					$q->where('id', $request->customer_id);
				});
			}
			
			$returns = $query->get();
			
			$data = [];
			foreach($returns as $return){
				$customer = optional(optional($return->customerInvoice)->customer);
				$data[] = [
					'id' => $return->id,
					'editable' => false,
					'product_id' => optional($return->product)->name ?? '',
					'quantity' => abs($return->stock),
					'price' => abs($return->price),
					'invoice_id' => $return->invoice_id,
					'note' => $return->remarks ?? '',
					'customer_id' => $customer->id ?? '',
					'date' => uk_ts($return->getRawOriginal('created_at'), 'Y-m-d H:i:s'),
					'invoices' => '',
					'total' => abs($return->stock) * abs($return->price),
					'customer' => $customer->name ?? '',
				];
			}
			
			return $this->successResponse($data);
			
		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
	}
	
	public function creditBalance(Request $request, $customer_id)
	{
		try {
			$tableExists = \Illuminate\Support\Facades\Schema::hasTable('customer_credit_usages');
			$earned    = CustomerCreditUsage::totalEarned($customer_id);
			$used      = $tableExists ? CustomerCreditUsage::totalUsed($customer_id) : 0;
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
			$earned = StockProduct::where('event', 'customer_return')
				->where('is_archived', 0)
				->where('type', 'customer')
				->get()
				->sum(fn($r) => abs($r->stock) * $r->price);
			$used = 0;
			if (\Illuminate\Support\Facades\Schema::hasTable('customer_credit_usages')) {
				$used = CustomerCreditUsage::sum('amount');
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

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function returnCreate(Request $request)
    {
		$productId = is_array($request->product_id) ? $request->product_id['value'] : $request->product_id;

		$detail = CustomerInvoiceProduct::where('customer_invoice_id', $request->invoice_id['invoice_id'])
			->where('id', $request->invoice_id['id'])->first();

		DB::beginTransaction();
		try{
			if(empty($detail)){
				throw new \Exception("Invalid Invoice!");
			}

			// Check available using same logic as GET endpoint (quantity - already returned)
			$returned = StockProduct::where('product_id', $productId)
				->where('invoice_id', $request->invoice_id['invoice_id'])
				->where('ref_id', $detail->id)
				->where('event', 'customer_return')
				->where('is_archived', 0)
				->sum('stock');

			$netStock = $detail->quantity - abs($returned);

			if($netStock <= 0){
				throw new \Exception("No stock available to return.");
			}

			if($request->quantity <= 0){
				throw new \Exception("Min 1 quantity is required.");
			}

			if($request->quantity > $netStock){
				throw new \Exception("Max quantity ".$netStock. " is allowed to return.");
			}

			$results = event(new CustomerReturnEvent([
				'supplier_invoice_product_id' => $detail->supplier_invoice_product_id,
				'supplier_invoice_id' => $detail->supplier_invoice_id,
				'customer_id' => $request->customer_id,
				'product_id' => $productId,
				'customer_invoice_id' => $request->invoice_id['invoice_id'],
				'customer_invoice_product_id' => $request->invoice_id['id'],
				'quantity' => $request->quantity,
				'price' => $request->price,
				'remarks' => $request->note,
				'type' => 'customer',
				'event' => 'customer_return',
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
		//print_r($request->all()); exit;
	
        // validation later.
		DB::beginTransaction();
		try{
		
			$current = StockProduct::where('id', $request->id)->where('invoice_id',$request->invoice_id)
				->where('customer_id', $request->customer_id)->where('is_archived', 0)->first();
			
			if(empty($current)){
				throw new \Exception("Invalid return requested to update.");
			}
			
			$stock = StockProduct::_productsWithInvoiceStockCustomer(
				$request->invoice_id,
				$request->customer_id,
				$current->product_id,
				$current->ref_id,[$request->id]
			);
			
			if(empty($stock)){
				throw new \Exception("Invalid return requested to update.");
			}
			
			if($stock->net_stock <= 0){
				throw new \Exception("Full Stock already used or returned.");
			}
			
			if($request->quantity > $stock->net_stock){
				throw new \Exception("Max quantity ".$stock->net_stock. " is allowed to return.");
			}
				
			$results = event(new CustomerReturnEvent([
				'id' => $request->id,
				'customer_id' => $request->customer_id,
				//'product_id' => $request->product_id['value'],
				'customer_invoice_id' => $request->invoice_id,
				//'customer_invoice_product_id' => $request->invoice_id['id'],
				'quantity' => $request->quantity,
				'price' => $request->price,
				//'remarks' => $request->note,
				//'type' => 'customer',
				'event' => 'customer_return',
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
			$results = event(new CustomerReturnEvent([
				'id' => $request->id,
				'delete' => 1,
				'customer_id' => $request->customer_id,
				'customer_invoice_id' => $request->invoice_id,
				'quantity' => $request->quantity,
				'price' => $request->price,
				'event' => 'customer_return',
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
