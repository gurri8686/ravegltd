<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lib\Response as CustomResponse;
use Validator;
use Response;
use Session;
use DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\ProductHistory;

class ProductHistoryController extends Controller
{
    use CustomResponse;
	
	public function index(Request $request){
		return view('product-history.index');
	}
	
	public function customers(Request $request){
		return $this->successResponse(\App\Models\Customer::getActive());
	}
	
	public function suppliers(Request $request){
		return $this->successResponse(\App\Models\Supplier::getActive());
	}
	
	public function products(Request $request){
		return $this->successResponse(\App\Models\Product::getActive());
	}
	
	// --------------
	public function supplierInvoices(Request $request){
		$rules = [
			'product_id' => 'required|integer'
		];

		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
		  return $this->validationErrorResponse($validator->errors()->messages());
		}
		
		$supplier_id = $product_id = $customer_id = $start_date = $end_date = "";
		
		extract($request->only('product_id', 'supplier_id', 'customer_id', 'start_date', 'end_date'));
		
		$records = \App\Models\SupplierInvoiceProduct
				::where('product_id', $product_id)
				->where('is_archive', 0)
				->whereDate('created_at', '>=', $start_date)
				->whereDate('created_at', '<=', $end_date);
		
		if(!empty($supplier_id)){
			$records->where('supplier_id', $supplier_id);
		}
		
		$records->select(['supplier_invoice_id','supplier_id','product_id','quantity','unit_price','sub_total','created_at','updated_at']);
		
		$records = $records
				->with(['product' => function($q){
					$q->select(['name','id','product_id']);
				}])
				->with(['supplier' => function($q){
					$q->select(['name','email','id','supplier_id']);
				}])
				->with('invoice')
				->get()->makeHidden(['invoice_title','invoice_title_short','customers']);
				
		return $this->successResponse($records);
	}
	
	public function supplierReturns(Request $request){
		$rules = [
			'product_id' => 'required|integer'
		];

		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
		  return $this->validationErrorResponse($validator->errors()->messages());
		}
		
		$supplier_id = $product_id = $customer_id = $start_date = $end_date = "";
		
		extract($request->only('product_id', 'supplier_id', 'customer_id', 'start_date', 'end_date'));
		
		$records = \App\Models\StockProduct::where('is_archived',0)
			->where('product_id', $product_id)
			->where('type', 'supplier')			
			->where('event', 'supplier_return')			
			->whereDate('created_at', '>=', $start_date)
			->whereDate('created_at', '<=', $end_date);
			
		if(!empty($supplier_id)){
			$records->where('supplier_id', $supplier_id);
		}
		$records->with(['supplier' => function($q){
			$q->select(['name','email','id','supplier_id']);
		}]);
		$records->with(['product' => function($q){
			$q->select(['name','id','product_id']);
		}]);
		$records = $records->get();
		
		return $this->successResponse($records);
	}
	
	public function sales(Request $request){
		$rules = [
			'product_id' => 'required|integer'
		];

		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
		  return $this->validationErrorResponse($validator->errors()->messages());
		}
		
		$supplier_id = $product_id = $customer_id = $start_date = $end_date = "";
		
		extract($request->only('product_id', 'supplier_id', 'customer_id', 'start_date', 'end_date'));
		
		$records = \App\Models\CustomerInvoiceProduct
			::where('is_archive',0)
			->where('product_id', $product_id)
			->whereDate('created_at', '>=', $start_date)
			->whereDate('created_at', '<=', $end_date);
			
		if(!empty($supplier_id)){
			$records->where('supplier_id', $supplier_id);
		}
		
		if(!empty($customer_id)){
			$records->where('customer_id', $customer_id);
		}
			
		$records->select(['supplier_invoice_id','customer_invoice_id','customer_id','supplier_id','product_id','quantity','unit_price','sub_total','created_at','updated_at']);
		
		$records->with(['supplier' => function($q){
			$q->select(['name','email','id','supplier_id']);
		}]);
		
		$records->with(['product' => function($q){
			$q->select(['name','id','product_id']);
		}]);
		
		$records->with(['customer' => function($q){
			$q->select(['name','id','email']);
		}])->with('customerInvoice');
		
		$records = $records->get();
		return $this->successResponse($records);
	}
	
	public function customerReturns(Request $request){
		$rules = [
			'product_id' => 'required|integer'
		];

		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
		  return $this->validationErrorResponse($validator->errors()->messages());
		}
		
		$supplier_id = $product_id = $customer_id = $start_date = $end_date = "";
		
		extract($request->only('product_id', 'supplier_id', 'customer_id', 'start_date', 'end_date'));
		
		$records = \App\Models\StockProduct
			::where('is_archived',0)
			->where('type', 'customer')			
			->where('event', 'customer_return')
			->where('product_id', $product_id)
			->whereDate('created_at', '>=', $start_date)
			->whereDate('created_at', '<=', $end_date);
			
		if(!empty($customer_id)){
			$records->where('customer_id', $customer_id);
		}
			
		$records->with(['product' => function($q){
			$q->select(['name','id','product_id']);
		}]);
		
		$records->with(['customer' => function($q){
			$q->select(['name','id','email']);
		}])->with('customerInvoice');
		
		$records = $records->get();
		return $this->successResponse($records);
	}
	
	public function dumps(Request $request){
		$rules = [
			'product_id' => 'required|integer'
		];

		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
		  return $this->validationErrorResponse($validator->errors()->messages());
		}
		
		$supplier_id = $product_id = $customer_id = $start_date = $end_date = "";
		
		extract($request->only('product_id', 'supplier_id', 'customer_id', 'start_date', 'end_date'));
		
		$records = \App\Models\StockProduct
			::where('is_archived',0)
			->where('type', 'supplier')			
			->where('event', 'dump')
			->where('product_id', $product_id)
			->whereDate('created_at', '>=', $start_date)
			->whereDate('created_at', '<=', $end_date);
			
		if(!empty($supplier_id)){
			$records->where('supplier_id', $supplier_id);
		}
			
		$records->with(['product' => function($q){
			$q->select(['name','id','product_id']);
		}]);
		
		$records->with(['supplier' => function($q){
			$q->select(['name','id']);
		}])->with('supplierInvoice');
		
		$records = $records->get();
		
		return $this->successResponse($records);
	}
	
	
}
