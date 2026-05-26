<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierInvoice extends Model
{
    use \App\Models\Traits\LogsActivityTrait;
    use HasFactory;
	
	protected $fillable  = ['supplier_id','invoice_type','salesman_id','other_invoice_id','delivery_no','supplier_invoice_no','awb','remarks'];

    public function supplier(){
        return $this->hasOne('App\Models\Supplier','id','supplier_id');
    }

    public function order(){
        return $this->hasOne('App\Models\SupplierInvoiceOrder','supplier_invoice_id','id');
    }
    public function product(){
        return $this->hasMany('App\Models\SupplierInvoiceProduct','supplier_invoice_id','id');
    }
	
	public function products(){
        return $this->hasMany('App\Models\SupplierInvoiceProduct','supplier_invoice_id','id');
    }
	
	public function oneProduct(){
        return $this->hasOne('App\Models\SupplierInvoiceProduct','supplier_invoice_id','id');
    }
    
    public function salesman(){
        return $this->hasOne('App\Models\User','id','salesman_id');
    }
	
	public function orderStart(){
        return $this->hasMany('App\Models\SupplierInvoiceProduct','supplier_invoice_id','id')->where('is_archive',0);
    }
	
	public function payments(){
        return $this->hasMany('App\Models\SupplierPayment','supplier_invoice_id','id')->where('is_archived',0);
    }
	
	public static function unpaidInvoices($supplier_id, $invoices = []){
		$query = SupplierInvoice::query();
		$query = $query
			->select('supplier_invoices.*')
			->selectSub(function ($query) {
				$query->from('supplier_invoice_products')
					->selectRaw('COALESCE(SUM(sub_total), 0)')
					->whereColumn('supplier_invoices.id', 'supplier_invoice_products.supplier_invoice_id')
					->where('is_archive', 0)
					;
			}, 'total_products')
			->selectSub(function ($query) {
				$query->from('supplier_payments')
					->selectRaw('COALESCE(SUM(amount), 0)')
					->whereColumn('supplier_invoices.id', 'supplier_payments.supplier_invoice_id')
					->where('is_archived', 0)
					//->where('is_discounted', 0)
					//->where('is_refunded', 0)
					//->where('is_credited', 0)
					;
			}, 'total_payments')
			->selectRaw('(COALESCE((
					select SUM(sub_total)
					from supplier_invoice_products
					where supplier_invoice_products.supplier_invoice_id = supplier_invoices.id
					and is_archive = 0
				), 0)
				- COALESCE((
					select SUM(amount)
					from supplier_payments
					where supplier_payments.supplier_invoice_id = supplier_invoices.id
					and is_archived = 0
				), 0)
			) as balance_due')
			->having('balance_due', '>', 0)
			->where('supplier_id', $supplier_id);
		if (!empty($invoices)) {
			$query = $query->whereIn('id', $invoices);
		}
		return $query->get();
	}
}
