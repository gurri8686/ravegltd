<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class CustomerPayment extends Model
{
	use \App\Models\Traits\LogsActivityTrait;
    use HasFactory;
	
	protected $fillable = [
		"customer_invoice_id",
		"customer_payment_id","is_paid",
		"debt",
		"credit",
		"is_refunded","amount","customer_id","initiated","is_credited","is_runtime_payment",
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
	
	public function products(){
        return $this->hasMany('App\Models\CustomerInvoiceProduct','customer_invoice_id','customer_invoice_id')->where('is_archive',0);
    }
	
	public function payments(){
        return $this->hasMany('App\Models\CustomerPayment','customer_invoice_id','customer_invoice_id')->where('is_archived',0);
    }
	
	public function orderStart(){
        return $this->hasMany('App\Models\CustomerInvoiceProduct','customer_invoice_id','customer_invoice_id')->where('is_archive',0);
    }
	
	public function childPayments()
    {
        return $this->hasMany(CustomerPayment::class, 'customer_payment_id', 'id');
    }
	
	public function paymentMode()
    {
        return $this->hasOne('App\Models\Payment', 'id','payment_id');
    }
	
	public function customer()
    {
        return $this->hasOne('App\Models\Customer', 'id','customer_id');
    }
	
	public static function creditedPayments($customer_id, $id = ""){
		$query = CustomerPayment::select(
			'customer_payments.id as payment_id',
			'customer_payments.amount as total_amount',
			'customer_payments.is_refunded',
			'customer_payments.is_credited',
			'customer_payments.is_discounted',
			'customer_payments.is_runtime_payment',
			'customer_payments.note',
			'customer_payments.payment_id as mode_id',
			'customer_payments.created_at',
			DB::raw('IFNULL(SUM(child.amount), 0) as paid_amount'),
			DB::raw('(customer_payments.amount - IFNULL(SUM(child.amount), 0)) as remaining_amount')
		)
		->leftJoin('customer_payments as child', 'child.customer_payment_id', '=', 'customer_payments.id')
		->where('customer_payments.is_paid', 0)
		->where('customer_payments.initiated', '!=', 1)
		->where('customer_payments.customer_id', $customer_id);
		
		if(!empty($id)){
			$query = $query->where('customer_payments.id', $id);
		}
		
		$query = $query->groupBy('customer_payments.id', 'customer_payments.amount');
		
		if(!empty($id)){
			$query = $query->first();
		}else{
			$query = $query->get();
		}
		
		return $query;
	}
	
}
