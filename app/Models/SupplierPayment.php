<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class SupplierPayment extends Model
{
	use \App\Models\Traits\LogsActivityTrait;
    use HasFactory;
	
	protected $fillable = [
		"supplier_invoice_id",
		"supplier_payment_id","is_paid",
		"debt",
		"credit",
		"is_refunded","amount","supplier_id","initiated","is_credited","is_runtime_payment",
		"payment_id",
		"note",
		"mode",
		"invoice_products",
		"created_at",
		"updated_at"
	];
	
	protected $appends = ['created_at_full'];

    // Accessor
	public function getCreatedAtFullAttribute()
	{
		return isset($this->attributes['created_at'])
			? \Carbon\Carbon::parse($this->attributes['created_at'])->format('d M Y, H:iA')
			: null;
	}
	
	public function orderStart(){
        return $this->hasMany('App\Models\SupplierInvoiceProduct','supplier_invoice_id','supplier_invoice_id')->where('is_archive',0);
    }
	
	public function childPayments()
    {
        return $this->hasMany(SupplierPayment::class, 'supplier_payment_id', 'id');
    }
	public function payments(){
        return $this->hasMany('App\Models\SupplierPayment','supplier_invoice_id','id')->where('is_archived',0);
    }
	
	public function products(){
        return $this->hasMany('App\Models\SupplierInvoiceProduct','supplier_invoice_id','supplier_invoice_id')
		->where('is_archive',0);
    }
	
	public function paymentMode()
    {
        return $this->hasOne('App\Models\Payment', 'id','payment_id');
    }
	
	public function invoice()
    {
        return $this->hasOne('App\Models\SupplierInvoice', 'id','supplier_invoice_id');
    }
	
	public function supplier()
    {
        return $this->hasOne('App\Models\Supplier', 'id','supplier_id');
    }
	
	public static function creditedPayments($supplier_id, $id = ""){
		$query = SupplierPayment::select(
			'supplier_payments.id as payment_id',
			'supplier_payments.amount as total_amount',
			'supplier_payments.is_refunded',
			'supplier_payments.is_credited',
			'supplier_payments.is_discounted',
			'supplier_payments.is_runtime_payment',
			'supplier_payments.note',
			'supplier_payments.created_at',
			DB::raw('IFNULL(SUM(child.amount), 0) as paid_amount'),
			DB::raw('(supplier_payments.amount - IFNULL(SUM(child.amount), 0)) as remaining_amount')
		)
		->leftJoin('supplier_payments as child', 'child.supplier_payment_id', '=', 'supplier_payments.id')
		->where('supplier_payments.is_paid', 0)
		->where('supplier_payments.initiated', '!=', 1)
		->where('supplier_payments.supplier_id', $supplier_id);
		
		if(!empty($id)){
			$query = $query->where('supplier_payments.id', $id);
		}
		
		$query = $query->groupBy('supplier_payments.id', 'supplier_payments.amount');
		
		if(!empty($id)){
			$query = $query->first();
		}else{
			$query = $query->get();
		}
		
		return $query;
	}
}
