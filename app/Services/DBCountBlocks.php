<?php

namespace App\Services;
use Illuminate\Http\Request;

use App\Models\SupplierInvoiceProduct;
use App\Models\CustomerInvoiceProduct;
use App\Models\StockProduct;
use App\Lib\Response;
use DB;

class DBCountBlocks{
	
	public static function salesAllTime(){
		return DB::select(
			"select sum(customer_invoice_products.sub_total) as amount from customer_invoice_products
			inner join invoice_payments on (invoice_payments.customer_invoice_id = customer_invoice_products.customer_invoice_id)
			inner join payments on (payments.id = invoice_payments.payment_id)
			where payments.type = 'None';"
		);
	}
	
	public static function salesAllToday(){
		return DB::select(
			"select sum(customer_invoice_products.sub_total) as amount from customer_invoice_products
			inner join invoice_payments on (invoice_payments.customer_invoice_id = customer_invoice_products.customer_invoice_id)
			inner join payments on (payments.id = invoice_payments.payment_id)
			where payments.type = 'None' AND DATE(customer_invoice_products.updated_at) = CURDATE();"
		);
	}
	
	public static function purchaseAllTime(){
		return DB::select(
			"select sum(supplier_invoice_products.sub_total) as amount from supplier_invoice_products
			where is_archive = 0"
		);
	}
	
	public static function purchaseToday(){
		return DB::select(
			"select sum(supplier_invoice_products.sub_total) as amount from supplier_invoice_products
			where is_archive = 0 AND DATE(created_at) = CURDATE();"
		);
	}
	
	public static function todayAllOrders(){
		return DB::select(
			"select count(*) as today_orders from customer_invoice_orders
				where DATE(updated_at) = CURDATE();"
		);
	}
	
	public static function customerPaymentsAll(){
		return DB::select(
			"select sum(customer_invoice_products.sub_total) as amount from customer_invoice_products
			inner join invoice_payments on (invoice_payments.customer_invoice_id = customer_invoice_products.customer_invoice_id)
			inner join payments on (payments.id = invoice_payments.payment_id)
			where payments.type != 'None';"	
		);
	}
	
	public static function customerPaymentsToday(){
		return DB::select(
			"select sum(customer_invoice_products.sub_total) as amount from customer_invoice_products
			inner join invoice_payments on (invoice_payments.customer_invoice_id = customer_invoice_products.customer_invoice_id)
			inner join payments on (payments.id = invoice_payments.payment_id)
			where payments.type != 'None' AND DATE(customer_invoice_products.created_at) = CURDATE();"	
		);
	}
	
	public static function dashboard(){
		return [
			'sales_today' => self::salesAllToday(),
			'purchase_today' => self::purchaseToday(),
			'sales_alltime' => self::salesAllTime(),
			'today_orders' => self::todayAllOrders(),
			'customers_alltime_payments' => self::customerPaymentsAll(),
			'customers_today_payments' => self::customerPaymentsToday(),
			'purchase_alltime' => self::purchaseAllTime(),
			'paid_unpaid_today' => self::paidUnpaindToday(),
			'latest_customers' => self::latestCustomers(),
		];
	} 
	
	public static function paidUnpaindToday(){
		return DB::select(
			"SELECT 
				SUM(payment_id = 1) AS unpaid,
				SUM(payment_id != 1) AS paid
			FROM invoice_payments
			where DATE(updated_at) = CURDATE();"
		);
	}
	
	public static function latestCustomers(){
		return \App\Models\Customer::orderBy('id','DESC')->limit(5)->get();
	}
		
	/**
	 * @example: \App\Services\DBCountBlocks $DBCountBlocks::productStockCount(1)
	 */
	public static function productStockCount($supplier_invoice_product_id){
		$supplier = SupplierInvoiceProduct::where('id',$supplier_invoice_product_id)->with('supplier')->first();
		$customers = CustomerInvoiceProduct::where('supplier_invoice_product_id',$supplier_invoice_product_id)->sum('quantity');		
		
		return [
			'supplier_invoice_id' => $supplier->supplier_invoice_id, 
			'supplier_invoice_product_id' => $supplier_invoice_product_id, 
			'supplier' => $supplier->quantity, 
			'customers' => $customers,
			'stock' => $supplier->quantity - $customers,
			'label' => (explode(' ', $supplier->supplier->name)[0])."...|Qty:".($supplier->quantity - $customers)."|P:".$supplier->unit_price.'|'.$supplier->remarks,
			'label_short' => $supplier->supplier->name
		];
	}
	
	public static function invoiceStockCount($customer_invoice_id){
		$invoice_products = CustomerInvoiceProduct
			::where('customer_invoice_id', $customer_invoice_id)
			->select('supplier_invoice_product_id')
			->get();
		$ids = $invoice_products->pluck('supplier_invoice_product_id')->toArray();
		$suppliers = SupplierInvoiceProduct::whereIn('id',$ids)->with('supplier')->get();
		
		$result = [];
		foreach($suppliers as $s){
			$customers = $s->customers;
			$result[] = [
				'supplier_invoice_id' => $s->supplier_invoice_id, 
				'supplier_invoice_product_id' => $s->id, 
				'supplier' => $s->quantity, 
				'customers' => (function() use($customers){
					$total = [];
					foreach($customers as $c){
						if($c->is_archive == 0){
							$total[] = $c->quantity;
						}
					}
					return array_sum($total);
				})(),
				'stock' => $s->quantity - (function() use($customers){
					$total = [];
					foreach($customers as $c){
						if($c->is_archive == 0){
							$total[] = $c->quantity;
						}
					}
					return array_sum($total);
				})(),
				'label' => $s->invoice_title,
				'label_short' => $s->invoice_title_short
			];
		}
		
		return $result;
	}
	
}