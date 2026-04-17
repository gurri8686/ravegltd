<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Lib\Response as CustomResponse;
use App\Models\CustomerInvoiceProduct;
use App\Models\StockProduct;
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
				$query->whereDate('updated_at', '>=', $request->date);
			}
			if($request->filled('to_date') && $request->to_date !== ''){
				$query->whereDate('updated_at', '<=', $request->to_date);
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
				$data[] = [
					'id' => $return->id,
					'editable' => false,
					'product_id' => $return->product->name,
					'quantity' => $return->stock,
					'price' => $return->price,
					'invoice_id' => $return->invoice_id,
					//'note' => $return->invoice_id,
					'customer_id' => $return->customerInvoice->customer->id,
					'date' => $return->updated_at,
					'invoices' => '',
					'total' => $return->stock * $return->price,
					'customer' => $return->customerInvoice->customer->name,
					'date' => $return->updated_at
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
	
		$detail = CustomerInvoiceProduct::where('customer_invoice_id', $request->invoice_id['invoice_id'])
			->where('id', $request->invoice_id['id'])->first();
		
		// validation later.
		DB::beginTransaction();
		try{
			if(empty($detail)){
				throw new \Exception("Invalid Invoice!");
			}
			$dateParam = $request->filled('date') ? $request->date : '2000-01-01';
			$stock = StockProduct::getProductsWithInvoiceStockCustomer(
				$request->customer_id,
				$request->product_id,
				$dateParam,
				$request->invoice_id['ref_id']
			);
			
			if(empty($stock)){
				throw new \Exception("Invalid Invoice Stock!");
			}
			
			if($request->quantity <= 0){
				throw new \Exception("Min 1 quantity is required.");
			}
			
			if($stock->net_stock <= 0){
				throw new \Exception("Max quantity alredy returned.");
			}
			
			if($request->quantity > $stock->net_stock){
				throw new \Exception("Max quantity ".$stock->net_stock. " is allowed to return.");
			}
			
			$results = event(new CustomerReturnEvent([
				'supplier_invoice_product_id' => $detail->supplier_invoice_product_id,
				'supplier_invoice_id' => $detail->supplier_invoice_id,
				'customer_id' => $request->customer_id,
				'product_id' => $request->product_id['value'],
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
