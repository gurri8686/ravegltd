<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class CustomerInvoiceProduct extends Model
{
    use \App\Models\Traits\LogsActivityTrait;
	use \Awobaz\Compoships\Compoships;
    use HasFactory;
	
    protected $fillable = [
        'customer_invoice_id','supplier_id','supplier_invoice_product_id', 'supplier_invoice_id','remarks', 'customer_id', 'product_id', 'product_details', 'quantity', 'unit_price','sub_total', 'is_archive','product_info'
    ];
    
    public function product(){
        return $this->hasOne('App\Models\Product','id','product_id');
    }
	
	public function customer(){
        return $this->hasOne('App\Models\Customer','id','customer_id');
    }
	
	public function customerInvoice(){
        return $this->hasOne('App\Models\CustomerInvoice','id','customer_invoice_id');
    }
	
	public function supplierInvoice(){
        return $this->hasOne('App\Models\SupplierInvoiceProduct','id','supplier_invoice_product_id');
    }
	
	public function returns(){
        return $this->hasMany('App\Models\StockProduct','invoice_id','customer_invoice_id')
			->where('type','customer')
			->where('is_archived',0)
			->where('event','customer_return');
    }
	
	public function supplier(){
        return $this->hasOne('App\Models\Supplier','id','supplier_id');
    }
	
	public static function getProductSuppliers($product_id){
		return CustomerInvoiceProduct::select(
			'product_id',
			'supplier_id',
			DB::raw('SUM(quantity) as total_quantity')
		)->with('product')->with('supplier')
		->where('product_id',$product_id)
		->groupBy('product_id', 'supplier_id')
		->get();
	}
	
	public static function getProductSupplier($product_id, $supplier_id){
		return CustomerInvoiceProduct::select(
			'product_id',
			'supplier_id',
			DB::raw('SUM(quantity) as total_quantity')
		)->with('product')->with('supplier')
		->where('product_id',$product_id)
		->where('supplier_id',$supplier_id)
		->groupBy('product_id', 'supplier_id')
		->first();
	}
}
