<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Lib\Response as CustomResponse;
use Validator;
use Response;
use Session;
use DB;

class ProductHistory{

	public static function supplierInvoices($product_id, $start_date, $end_date, $supplier_id = ""){
		$records = \App\Models\SupplierInvoiceProduct
				::where('product_id', $product_id)
				->where('is_archive', 0);

		if(!empty($start_date)) $records->whereDate('created_at', '>=', $start_date);
		if(!empty($end_date))   $records->whereDate('created_at', '<=', $end_date);

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
		return $records;
	}

	public static function supplierReturns($product_id, $start_date, $end_date, $supplier_id = ""){
		$records = \App\Models\StockProduct::where('is_archived',0)
			->where('product_id', $product_id)
			->where('type', 'supplier')
			->where('event', 'supplier_return');

		if(!empty($start_date)) $records->whereDate('created_at', '>=', $start_date);
		if(!empty($end_date))   $records->whereDate('created_at', '<=', $end_date);

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
		return $records;
	}

	public static function sales($product_id, $start_date, $end_date, $customer_id = "", $supplier_id = ""){
		$records = \App\Models\CustomerInvoiceProduct
			::where('is_archive',0)
			->where('product_id', $product_id);

		if(!empty($start_date)) $records->whereDate('created_at', '>=', $start_date);
		if(!empty($end_date))   $records->whereDate('created_at', '<=', $end_date);

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
		return $records;
	}

	public static function customerReturns($product_id, $start_date, $end_date, $customer_id = ""){
		$records = \App\Models\StockProduct
			::where('is_archived',0)
			->where('type', 'customer')
			->where('event', 'customer_return')
			->where('product_id', $product_id);

		if(!empty($start_date)) $records->whereDate('created_at', '>=', $start_date);
		if(!empty($end_date))   $records->whereDate('created_at', '<=', $end_date);

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
		return $records;
	}

	public static function dumps($product_id, $start_date, $end_date, $supplier_id = ""){
		$records = \App\Models\StockProduct
			::where('is_archived',0)
			->where('type', 'supplier')
			->where('event', 'dump')
			->where('product_id', $product_id);

		if(!empty($start_date)) $records->whereDate('created_at', '>=', $start_date);
		if(!empty($end_date))   $records->whereDate('created_at', '<=', $end_date);

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

		return $records;
	}

}
