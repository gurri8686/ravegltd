<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class Product extends Model
{
    use \App\Models\Traits\LogsActivityTrait;
    use HasFactory;
    
    public function user(){
        return $this->hasOne('App\Models\User','id','user_id');
    }
	
	public static function productsData(){
		return DB::table('products')
		->rightJoin('supplier_invoice_products', 'products.id', '=', 'supplier_invoice_products.product_id')
		->rightJoin('suppliers', 'suppliers.id', 'supplier_invoice_products.supplier_id')
		->select('products.*', 
			'suppliers.name as supplier_name' ,
			'suppliers.id as supplier_id',
			'supplier_invoice_products.supplier_invoice_id',
			'supplier_invoice_products.quantity',
			'supplier_invoice_products.unit_price',
			'supplier_invoice_products.product_info')
		->get();
	}
	
	public static function getActive(){
		return self::all();
	}
	
	public function stockClosing(){
        return $this->hasOne('App\Models\StockClosing','product_id','id')->withDefault([
			'stock' => '0'
		]);
    }
	
	public function stockProducts(){
        return $this->hasMany('App\Models\StockProduct','product_id','id');
    }
	
	public function supplierProducts(){
        return $this->hasMany('App\Models\SupplierInvoiceProduct','product_id','id')->where('is_archive',0);
    }
	
	public function customerProducts(){
        return $this->hasMany('App\Models\CustomerInvoiceProduct','product_id','id')->where('is_archive',0);
    }
	
	public function supplierReturns(){
        return $this->hasMany('App\Models\StockProduct','product_id','id')
			->where('type','supplier')
			->where('is_archived',0)
			->where('event','supplier_return');
    }
	
	public function customerReturns(){
        return $this->hasMany('App\Models\StockProduct','product_id','id')
			->where('type','customer')
			->where('is_archived',0)
			->where('event','customer_return');
    }
	
	public function dumps(){
        return $this->hasMany('App\Models\StockProduct','product_id','id')
			->where('type','supplier')
			->where('is_archived',0)
			->where('event','dump');
    }
}
