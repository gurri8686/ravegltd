<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Lib\Response as CustomResponse;
use App\Models\SupplierInvoiceProduct;
use App\Models\StockProduct;
use App\Events\SupplierReturnEvent;
use DB;

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
	
	public function suppliers()
    {
        return $this->successResponse(\App\Models\Supplier::getActive());
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
				$request->date, ""
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
			$rules = [
				'date' => 'required',
            ];
			
			$validator = Validator::make($request->all(), $rules);
			
			if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
			
			$query = StockProduct
				::where('is_archived',0)
				->where('event','supplier_return')
				->where('type','supplier')
				->whereDate('updated_at', '>=', $request->date)
				->when($request->end_date, function($q) use ($request) {
					$q->whereDate('updated_at', '<=', $request->end_date);
				})
				->with('product')
				->with('supplier')
				;
				
			if ($request->has('supplier_id') && !empty($request->supplier_id)) {
				$query->where('supplier_id',$request->supplier_id);
			}
			
			/*if ($request->has('supplier_id') && !empty($request->supplier_id)) {
				$query->whereHas('customerInvoice.customer', function ($q) use ($request) {
					$q->where('id', $request->supplier_id);
				});
			}*/
			
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
					'supplier_id' => $return->supplier->id,
					'date' => $return->updated_at,
					'invoices' => '',
					'total' => $return->stock * $return->price,
					'supplier' => $return->supplier->name,
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
		// validation later.
		DB::beginTransaction();
		try{
			/*$detail = SupplierInvoiceProduct::where('supplier_invoice_id', $request->invoice_id['invoice_id'])
			->where('id', $request->invoice_id['id'])
			->where('is_archive', 0)
			->first();
					
			if(empty($detail)){
				throw new \Exception("Invalid Invoice!");
			}
			*/
			$stock = StockProduct::_productsWithInvoiceStockSupplier(
				$request->invoice_id['invoice_id'],
				$request->supplier_id,
				$request->product_id['value'],
				$request->invoice_id['id']
			);
			
			if(empty($stock)){
				throw new \Exception("Invalid Invoice Stock!");
			}
			
			if($request->quantity <= 0){
				throw new \Exception("Min 1 quantity is required.");
			}
			
			if($stock['net_stock'] <= 0){
				throw new \Exception("Max quantity alredy returned.");
			}
			
			if($request->quantity > $stock['net_stock']){
				throw new \Exception("Max quantity ".$stock['net_stock']. " is allowed to return.");
			}
			
			$results = event(new SupplierReturnEvent([
				'supplier_id' => $request->supplier_id,
				'product_id' => $request->product_id['value'],
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
}
