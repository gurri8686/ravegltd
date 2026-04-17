<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class CustomerInvoice extends Model
{
    use \App\Models\Traits\LogsActivityTrait;
    use HasFactory;

    public function customer(){
        return $this->hasOne('App\Models\Customer','id','customer_id');
    }

    public function order(){
        return $this->hasOne('App\Models\CustomerInvoiceOrder','customer_invoice_id','id');
    }

    public function invoicePayment(){
        return $this->hasOne('App\Models\InvoicePayment','customer_invoice_id','id')->orderBy('id', 'DESC');
    }

    public function product(){
        return $this->hasMany('App\Models\CustomerInvoiceProduct','customer_invoice_id','id');
    }
	
	public function oneProduct(){
        return $this->hasOne('App\Models\CustomerInvoiceProduct','customer_invoice_id','id');
    }
	
	public function products(){
        return $this->hasMany('App\Models\CustomerInvoiceProduct','customer_invoice_id','id')->where('is_archive',0);
    }

    public function salesman(){
        return $this->hasOne('App\Models\User','id','salesman_id');
    }
	
	public function paidPayments(){
        return $this->hasMany('App\Models\CustomerPayment','customer_invoice_id','id')
			->where('is_archived',0)
			//->where('is_discounted',0)->where('is_refunded',0)->where('is_credited',0)
			;
    }
	
	public function payments(){
        return $this->hasMany('App\Models\CustomerPayment','customer_invoice_id','id')->where('is_archived',0);
    }
	
	public function orderStart(){
        return $this->hasMany('App\Models\CustomerInvoiceProduct','customer_invoice_id','id')->where('is_archive',0);
    }
	
	public function getNotUsedInvoice(){
		$ids = CustomerInvoice::all();
		return DB::table('customer_invoices')
		->where('status',0)
		->whereDate('customer_invoices.created_at', now()->toDateString())
		->leftJoin('customer_invoice_products', 'customer_invoices.id', '=', 'customer_invoice_products.customer_invoice_id')
		->whereNull('customer_invoice_products.id')
		->select('customer_invoices.*')
		->first();
	}
	
	public static function unpaidInvoices($customer_id, $invoices = []){
		$query = CustomerInvoice::query();
		return $query
			->select('customer_invoices.*')
			->selectSub(function ($query) {
				$query->from('customer_invoice_products')
					->selectRaw('COALESCE(SUM(sub_total), 0)')
					->whereColumn('customer_invoices.id', 'customer_invoice_products.customer_invoice_id')
					->where('is_archive', 0)
					;
			}, 'total_products')
			->selectSub(function ($query) {
				$query->from('customer_payments')
					->selectRaw('COALESCE(SUM(amount), 0)')
					->whereColumn('customer_invoices.id', 'customer_payments.customer_invoice_id')
					->where('is_archived', 0)
					//->where('is_discounted', 0)
					//->where('is_refunded', 0)
					//->where('is_credited', 0)
					;
			}, 'total_payments')
			->selectRaw('(COALESCE((
					select SUM(sub_total)
					from customer_invoice_products
					where customer_invoice_products.customer_invoice_id = customer_invoices.id
					and is_archive = 0
				), 0)
				- COALESCE((
					select SUM(amount)
					from customer_payments
					where customer_payments.customer_invoice_id = customer_invoices.id
					and is_archived = 0
				), 0)
			) as balance_due')
			->having('balance_due', '>', 0)
			->where('customer_id', $customer_id)
			->whereIn('id',$invoices)->get();
	}
	
	public static function invoiceDetail($customer_invoice_id){
		$query = CustomerInvoice::query();
		return $query
			->select('customer_invoices.*')
			->selectSub(function ($query) {
				$query->from('customer_invoice_products')
					->selectRaw('COALESCE(SUM(sub_total), 0)')
					->whereColumn('customer_invoices.id', 'customer_invoice_products.customer_invoice_id')
					->where('is_archive', 0)
					;
			}, 'total_products')
			->selectSub(function ($query) {
				$query->from('customer_payments')
					->selectRaw('COALESCE(SUM(amount), 0)')
					->whereColumn('customer_invoices.id', 'customer_payments.customer_invoice_id')
					->where('is_archived', 0)
					//->where('is_discounted', 0)
					//->where('is_refunded', 0)
					//->where('is_credited', 0)
					;
			}, 'total_payments')
			->selectRaw('(COALESCE((
					select SUM(sub_total)
					from customer_invoice_products
					where customer_invoice_products.customer_invoice_id = customer_invoices.id
					and is_archive = 0
				), 0)
				- COALESCE((
					select SUM(amount)
					from customer_payments
					where customer_payments.customer_invoice_id = customer_invoices.id
					and is_archived = 0
				), 0)
			) as balance_due')
			->having('balance_due', '>', 0)
			//->where('customer_id', $customer_id)
			->where('id',$customer_invoice_id)->first();
	}
}
