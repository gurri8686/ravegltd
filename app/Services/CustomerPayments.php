<?php

namespace App\Services;
use Illuminate\Http\Request;

use App\Models\SupplierInvoiceProduct;
use App\Models\CustomerInvoiceProduct;
use App\Models\CustomerInvoice;
use App\Models\CustomerPayment;
use App\Lib\Response as CustomResponse;
use Validator;
use Response;
use Session;
use DB;

class CustomerPayments{
	
	use CustomResponse;
	
	public static function onAccountPayment($data){
		return CustomerPayment::create([
			'customer_id' => $data['customer_id'],
			'mode' => 'original',
			'amount' => $data['amount'],
			'note' => $data['note'],
			'is_credited' => 1,
			'created_at' => $data['date'] . ' ' . now()->format('H:i:s'),
			'payment_id' => $data['payment_mode']
		]);
	}
	
	public static function onAccountPaymentList($customer_id){
		return CustomerPayment::where('customer_id', $customer_id)
			->where('is_credited',1)->orderBy('id', 'DESC')->get();
	}
	
	// check if invoice has one product added if added not then add new record.
	public static function initiateInvoice($customer_invoice_id, $customer_id){
		$cp = CustomerPayment
			::where('customer_invoice_id',$customer_invoice_id)
			->where('initiated',1)->first();
			
		if(empty($cp)){
			$cp = CustomerPayment::create([
				'customer_id' => $customer_id,
				'customer_invoice_id' => $customer_invoice_id,
				'initiated' => 1,
				'mode' => ''
			]);
		}else{
			$cp = CustomerPayment
			::where('customer_invoice_id',$customer_invoice_id)
			->where('initiated',1)->update(['customer_id' => $customer_id]);
		}
		return $cp;
	}
	
	public static function list($id){
		return CustomerPayment::where('customer_invoice_id',$id)->where('is_archived',0)
		->where('initiated',0)->where('is_paid',1)->orderBy('id','DESC')->get();
	} 
	
	public static function details($id){
		return self::checkAlreadyPaid($id);
	} 
	
	public static function checkAlreadyPaid($customer_invoice_id){
		$payments = CustomerPayment::where('customer_invoice_id',$customer_invoice_id)
			->where('is_archived',0)
			->get();
		$invoice = CustomerInvoice::where('id',$customer_invoice_id)->withSum('orderStart','sub_total')->first();
		
		$report = [];
		
		if(sizeof($payments) <= 0){
			$report['paid'] = 0;
			$report['countable'] = 'no';
		}
		
		$total_credits = [];
		$total_debits = [];
		$total_paid = [];
				
		foreach($payments as $p){
			$total_paid[] = $p->amount;
			if($p->credit > 0){
				$total_credits[] = $p->credit;
			}
		}
		
		$calc = $invoice->order_start_sum_sub_total - (array_sum($total_paid) + array_sum($total_credits));
		
		/*echo array_sum($total_paid); exit;
		
		if($calc == 0){
			$report['paid'] = array_sum($total_paid);
			$report['countable'] = 'yes';
		}
		if($calc > 0){
			$report['paid'] = array_sum($total_paid);
			$report['countable'] = 'yes';
		}
		if($calc < 0){
			$report['paid'] = array_sum($total_paid);
			$report['countable'] = 'yes';
		}*/
		
		return ['total_paid' => (array_sum($total_paid) + array_sum($total_credits)), 'paid' => abs($calc), 'total' => $invoice->order_start_sum_sub_total, 'type' => $calc < 0 ? 'No Payment Pending / Advance' : 'Pending'];
	}
	
	public static function checkAlreadyPaid_V2($customer_invoice_id){
		$payments = CustomerPayment::where('customer_invoice_id',$customer_invoice_id)->get();
		$report = [];
		
		if(sizeof($payments) <= 0){
			$report['paid'] = 0;
			$report['countable'] = 'no';
		}
		
		$total_credits = [];
		$total_debits = [];
		$total_paid = [];
		foreach($payments as $p){
			$total_paid[] = $p->amount;
			if($p->credit > 0){
				$total_credits[] = $p->credit;
			}
			
			if($p->debt > 0){
				$total_debits[] = $p->debt;
			}
		}
		
		//print_r($total_paid); exit;
		
		$calc = array_sum($total_debits) - array_sum($total_credits);
		
		if($calc == 0){
			$report['paid'] = array_sum($total_paid);
			$report['countable'] = 'yes';
		}
		if($calc > 0){
			$report['paid'] = array_sum($total_paid);
			$report['countable'] = 'yes';
		}
		if($calc < 0){
			$report['paid'] = array_sum($total_paid);
			$report['countable'] = 'yes';
		}
		
		return $report;
	}
	
	public static function fullAmountPayable($customer_invoice_id, $amount, $mode,$note = ""){
		
	}
	
	public static function partialAmountPayable($customer_invoice_id, $amount, $payment_mode, $note = ""){
		$payments = self::checkAlreadyPaid($customer_invoice_id);
		
		$invoice_main = CustomerInvoice::where('id',$customer_invoice_id)->where('status',1)->first();
		$invoice = CustomerInvoiceProduct::where('customer_invoice_id',$customer_invoice_id)
			->where('is_archive',0)->get();
		
		if(empty($invoice_main)){
			throw new \Exception(__('messages.invoice_not_found'));
		}
		
		if(sizeof($invoice) <= 0){
			throw new \Exception(__('messages.invoice_has_no_products'));
		}
		
		$total = [];
		foreach($invoice as $i){
			$total[] = $i->sub_total;
		}
		$total = array_sum($total);
		
		//echo $total; exit;
		
		$pending = $total - $payments['paid'];
		//echo $total - $payments['total_paid']; exit;
		//print_r($payments);echo $pending; exit;
		//echo $total.'-'.$payments['total_paid']; exit;
		
		//if($total <= ($payments['total_paid'])){
		if(($total - $payments['total_paid']) < ($amount)){
			$remaining = $total - $payments['total_paid'];
			throw new \Exception("Payment amount exceeds the remaining balance. Remaining: {$remaining}");
		}

		$calc = $pending - $amount;
		$credit = 0;
		$debit = 0;
		
		//echo $calc; exit;
				
		/*if($calc < 0){
			$credit = abs($calc);
			$paid = abs($pending);
		}else if($calc > 0){
			$debit = $calc;
			$paid = $amount;
		}else{
			$paid = $amount;
		}*/
		$calc = abs($calc);
		
		//echo $calc; exit;
		//echo $total.'---'. $debit; echo '---'; echo $credit; exit;
		DB::beginTransaction();
		try{
			// advanced payment based.
			$parent = CustomerPayment::create([
				'customer_invoice_id' => 0,
				'debt' => 0.00,
				'amount' => $amount,
				'is_runtime_payment' => 1,
				'credit' => 0.00,
				'customer_id' => $invoice_main->customer_id,
				'is_refunded' => 0,
				'payment_id' => $payment_mode,
				'note' => $note,
				'mode' => 'partial'
			]);
			$r = CustomerPayment::create([
				'customer_invoice_id' => $customer_invoice_id,
				'debt' => 0.00,
				'amount' => $amount,
				'is_paid' => 1,
				'credit' => 0.00,
				'customer_id' => $invoice_main->customer_id,
				'customer_payment_id' => $parent->id,
				'is_refunded' => 0,
				'payment_id' => $payment_mode,
				'note' => $note,
				'mode' => 'partial'
			]);

			DB::commit();

		}catch(\Exception $ex){
			DB::rollback();
			throw $ex;
		}

		return $r;
	}
	
	public static function partialAmountPayable_V2($customer_invoice_id, $amount, $payment_mode, $note = ""){
		$payments = self::checkAlreadyPaid($customer_invoice_id);
		
		$invoice_main = CustomerInvoice::where('id',$customer_invoice_id)->where('status',1)->first();
		$invoice = CustomerInvoiceProduct::where('customer_invoice_id',$customer_invoice_id)
			->where('is_archive',0)->get();
		
		if(empty($invoice_main)){
			throw new \Exception(__('messages.invoice_not_found'));
		}
		
		if(sizeof($invoice) <= 0){
			throw new \Exception(__('messages.invoice_has_no_products'));
		}
		
		$total = [];
		foreach($invoice as $i){
			$total[] = $i->sub_total;
		}
		$total = array_sum($total);
		$pending = $total - $payments['paid'];
		
		//print_r($payments);echo $pending; exit;
		//echo $total.'-'.$payments['paid']; exit;
		if($total <= $payments['paid']){
			throw new \Exception('messages.invoice_arready_full_paid');
		}

		$calc = $pending - $amount;
		$credit = 0;
		$debit = 0;
		
		//echo $calc; exit;
				
		if($calc < 0){
			$credit = abs($calc);
			$paid = abs($pending);
		}else if($calc > 0){
			$debit = $calc;
			$paid = $amount;
		}else{
			$paid = $amount;
		}
		$calc = abs($calc);
		
		//echo $calc; exit;
		//echo $total.'---'. $debit; echo '---'; echo $credit; exit;
		
		$r = CustomerPayment::create([
			'customer_invoice_id' => $customer_invoice_id,
			'parent_id' => 0,
			'debt' => $debit,
			'amount' => $paid,
			'credit' => abs($credit),
			'customer_id' => $invoice_main->customer_id,
			'is_refunded' => 0,
			'payment_id' => $payment_mode,
			'note' => $note,
			'mode' => ($credit >= $total ? 'full' : 'partial')
		]);
		
		return $r;
	}
	
	// parent_id in use.
	public static function payMultipleInvoices($customer_invoice_ids, $id, $note = ""){
	
	}
	
	public static function refundInvoice($customer_invoice_id, $amount, $mode, $note = ""){
	
	}
	
	public static function payMultipleInvoicesFromCredit($invoice_id,$customer_invoice_ids, $amount ){
		
	}
	
	public static function invoicePayments_V2($customer_id, $startDate, $endDate){
		$payments = CustomerPayment::where('customer_id',$customer_id)
			->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
			->where('is_archived',0)
			->withSum(['products as start_invoice' => function ($q) use($startDate, $endDate) {
					$q->where('is_archive', 0)
					->whereDate('created_at', '>=', $startDate)
					->whereDate('created_at', '<=', $endDate);
				}], 'sub_total')
			->get();
			
		echo '<pre>';	
		print_r($payments->toArray()); exit;	
		return $payments;
	} 
	
	public static function invoicePayments_V1($customer_id, $startDate, $endDate){
		$invoices = CustomerInvoice::
			where('customer_id', $customer_id)
			->whereDate('created_at', '>=', $startDate)
			->whereDate('created_at', '<=', $endDate)
			->where('status', 1)
			->with('payments')
			->withSum('orderStart','sub_total')
			->get();
		
		foreach($invoices as $k => $v){
			$credit = [];
			$debt = [];
			foreach($v->payments as $k2 => $v2){
				$credit[] = $v2->credit;
				$credit[] = $v2->amount;
			}
			$invoices[$k]['credit'] = array_sum($credit);
			$invoices[$k]['debt'] = $v->order_start_sum_sub_total - array_sum($credit);
		}
		
		/*echo '<pre>';	
		print_r($invoices->toArray()); exit;*/
		return $invoices;
	} 
	
	public static function invoicePaymentsHistory($customer_id, $startDate, $endDate){
		//echo $customer_id.'-'.$startDate.'-'.$endDate; exit;
		$invoices = CustomerPayment::where('is_archived', 0)
		->where(function ($q) use ($customer_id, $startDate, $endDate) {
			$q->where('initiated', 1)
			  ->where('customer_id', $customer_id)
			  ->whereDate('created_at', '>=', $startDate)
			  ->whereDate('created_at', '<=', $endDate);
		})
		->orWhere(function ($q) use ($customer_id, $startDate, $endDate) {
			$q->where('is_credited', 1)
			  ->where('customer_id', $customer_id)
			  ->whereDate('created_at', '>=', $startDate)
			  ->whereDate('created_at', '<=', $endDate);
		})
		// Load childPayments ONLY if EMPTY or SUM = 0
		->where(function ($q) {
			$q->whereDoesntHave('childPayments')     // no child payments at all
			  ->orWhereHas('childPayments', function ($c) {
				  $c->selectRaw('COALESCE(SUM(amount), 0) as total_amount')
					->having('total_amount', '=', 0); // sum = 0
			  });
		})
		->withSum('orderStart', 'sub_total')
		->with(['payments' => function ($q) {
			$q->where('is_paid', 1)
			  ->with('paymentMode');
		}])
		->with(['products' => function ($q) use($customer_id) {
			$q->where('customer_id', $customer_id)->groupBy('customer_invoice_id');
			$q->with(['returns']);
		}])
		// only includes the above filtered childPayments
		->withSum('childPayments', 'amount')
		->get();
		
		if(sizeof($invoices) <= 0){
			return [];
		}
		//echo '<pre>'; print_R($invoices->toArray()); exit;
		$data = [];
		$i = 0;
		foreach($invoices as $k => $v){
			$data[$i]['id'] =  $v->customer_invoice_id;
			//$data[$i]['balance'] =  $v->order_start_sum_sub_total - $v->total_amount;
			$data[$i]['created_at'] =  $v->created_at;
			$data[$i]['is_credited'] =  $v->is_credited;
			$data[$i]['net_amount'] =  (float)$v->order_start_sum_sub_total;
			$data[$i]['total_paid'][] = 0;
			
			$data[$i]['paid_by_card'][] = 0;
			$data[$i]['paid_by_cash'][] = 0;
			$data[$i]['paid_by_cheque'][] = 0;
			$data[$i]['paid_by_bank'][] = 0;
			
			if(sizeof($v->payments) > 0){
				$data[$i]['paid_by_card'][] = $data[$i]['paid_by_cash'][] = $data[$i]['paid_by_cheque'][] = $data[$i]['paid_by_bank'][] = 0;
				foreach($v->payments as $p){
					$data[$i]['total_paid'][] = $p->amount;
					if($p->is_paid == 1 && $p->paymentMode){
						if($p->paymentMode->type == 'Card'){
							$data[$i]['paid_by_card'][] = $p->amount;
						}
						if($p->paymentMode->type == 'Cash'){
							$data[$i]['paid_by_cash'][] = $p->amount;
						}
						if($p->paymentMode->type == 'Cheque'){
							$data[$i]['paid_by_cheque'][] = $p->amount;
						}
						if($p->paymentMode->type == 'Bank Transfer'){
							$data[$i]['paid_by_bank'][] = $p->amount;
						}
					}
				}
			}
			$data[$i]['returns'][] = 0;
			if(sizeof($v->products) > 0){
				foreach($v->products as $prod){
					if(sizeof($prod->returns) > 0){
						foreach($prod->returns as $r){
							$data[$i]['returns'][] = $r->price * $r->stock;
						}
					}
				}
			}
			$data[$i]['credit_adj'] = array_sum($data[$i]['returns']);
			$data[$i]['total_discounted'] = 0;
			$data[$i]['paid_by_card'] = array_sum($data[$i]['paid_by_card']);
			$data[$i]['paid_by_cash'] = array_sum($data[$i]['paid_by_cash']);
			$data[$i]['paid_by_cheque'] = array_sum($data[$i]['paid_by_cheque']);
			$data[$i]['paid_by_bank'] = array_sum($data[$i]['paid_by_bank']);
			
			if($v->is_credited == 1){
				$data[$i]['balance'] = -(float)$v->amount;
			}else{
				$data[$i]['balance'] = $v->order_start_sum_sub_total - array_sum($data[$i]['total_paid']) - $data[$i]['credit_adj'];
			}
			
			if($v->is_credited == 1){
				$data[$i]['total_paid'] = (float)$v->amount;
			}else{
				$data[$i]['total_paid'] = array_sum($data[$i]['total_paid']);
			}
			
			$i++;
		}
		return $data;
	}
	
	public static function invoicePayments($customer_id, $startDate, $endDate){
		$invoices = $payments = CustomerPayment::selectRaw("
					CASE 
						WHEN customer_invoice_id = 0 
							THEN CONCAT('id_', id)
						ELSE CONCAT('inv_', customer_invoice_id)
					END as group_key,
					SUM(amount) as total_debt,
					is_credited,
					is_refunded,
					is_discounted,
					note,
					SUM(credit) as total_credit,
					SUM(amount) as total_amount,
					MAX(customer_invoice_id) as customer_invoice_id,
					MAX(customer_id) as customer_id,
					MAX(is_archived) as is_archived,
					MIN(created_at) as created_at
				")
				->with(['payments' => function ($q) {
					$q->where('customer_invoice_id', '!=', 0);
					$q->with('paymentMode');
				}])
				->with(['products' => function($q){
					//$q->select(['customer_id','product_id','customer_invoice_id']);
					$q->with(['returns' => function($q){
						//$q->select(['price','stock','product_id','customer_id','invoice_id']);
					}]);
				}])
				->withSum('childPayments','amount')
				->withSum('orderStart', 'sub_total')
				->where('is_archived', 0)
				->where('customer_id', $customer_id)
				->whereDate('created_at', '>=', $startDate)
				->whereDate('created_at', '<=', $endDate)
				->groupBy('group_key')
				->orderBy('id', 'ASC')
				->get();

		
		//echo '<pre>'; print_R($invoices->toArray()); exit;
		
		$data = [];
		$i = 0;
		/*foreach($invoices as $k => $v){
			$data[$i]['id'] =  $v->customer_invoice_id;
			$data[$i]['total_paid'] =  $v->total_amount;
			$data[$i]['balance'] =  $v->order_start_sum_sub_total - $v->total_amount;
			$data[$i]['created_at'] =  $v->created_at;
			$data[$i]['is_credited'] =  $v->is_credited;
			if(sizeof($v->payments) > 0){
				$data[$i]['paid_by_card'][] = $data[$i]['paid_by_cash'][] = $data[$i]['paid_by_cheque'][] = $data[$i]['paid_by_bank'][] = 0;
				foreach($v->payments as $p){
					if($p->is_paid == 1){
						if($p->paymentMode->type == 'Card'){
							$data[$i]['paid_by_card'][] = $p->amount;
						}
						if($p->paymentMode->type == 'Cash'){
							$data[$i]['paid_by_cash'][] = $p->amount;
						}
						if($p->paymentMode->type == 'Cheque'){
							$data[$i]['paid_by_cheque'][] = $p->amount;
						}
						if($p->paymentMode->type == 'Bank Transfer'){
							$data[$i]['paid_by_bank'][] = $p->amount;
						}
					}
				}
			}
			if(sizeof($v->products) > 0){
				foreach($v->products as $prod){
					if(sizeof($prod->returns) > 0){
						foreach($prod->returns as $r){
							$data[$i]['returns'][] = $r->price * $r->stock;
						}
					}
				}
			}
			$i++;
		}*/
		
		//echo '<pre>'; print_r($data);exit;
		
		foreach($invoices as $k => $v){
			$credit = [];
			$debt = [];
			foreach($v->payments as $k2 => $v2){
				$credit[] = $v2->credit;
				$credit[] = $v2->amount;
			}
			if($v->customer_invoice_id == 0){
				$credit[] = $v->total_amount;
			}
			$invoices[$k]['credit'] = array_sum($credit);
			$invoices[$k]['debt'] = $v->order_start_sum_sub_total - array_sum($credit);
		}
		
		//echo '<pre>';print_r($invoices->toArray()); exit;
		
		return $invoices;

	}
	
	public static function pastBalance($customer_id, $startDate = ""){
		$invoices = CustomerPayment::where('is_archived', 0)
		->where(function ($q) use ($customer_id, $startDate) {
			$q->where('initiated', 1)
			  ->where('customer_id', $customer_id)
			  //->whereDate('created_at', '>=', $startDate)
			  ->whereDate('created_at', '<', $startDate);
		})
		->orWhere(function ($q) use ($customer_id, $startDate) {
			$q->where('is_credited', 1)
			  ->where('customer_id', $customer_id)
			  //->whereDate('created_at', '>=', $startDate)
			  ->whereDate('created_at', '<', $startDate);
		})
		// Load childPayments ONLY if EMPTY or SUM = 0
		->where(function ($q) {
			$q->whereDoesntHave('childPayments')     // no child payments at all
			  ->orWhereHas('childPayments', function ($c) {
				  $c->selectRaw('COALESCE(SUM(amount), 0) as total_amount')
					->having('total_amount', '=', 0); // sum = 0
			  });
		})
		->withSum('orderStart', 'sub_total')
		->with(['payments' => function ($q) {
			$q->where('is_paid', 1)
			  ->with('paymentMode');
		}])
		->with(['products' => function ($q) use($customer_id) {
			$q->where('customer_id', $customer_id)->groupBy('customer_invoice_id');
			$q->with(['returns']);
		}])
		// only includes the above filtered childPayments
		->withSum('childPayments', 'amount')
		->get();
		
		if(sizeof($invoices) <= 0){
			return 0;
		}		
		//echo '<pre>'; print_R($invoices->toArray()); exit;
		$data = [];
		$i = 0;
		
		$calc = [];
		$calc['returns'][] = 0;
		$calc['paid'][] = 0;
		$calc['on_account'][] = 0;
		$calc['net_amount'][] = 0;
		
		foreach($invoices as $k => $v){
			$data[$i]['id'] =  $v->customer_invoice_id;
			//$data[$i]['balance'] =  $v->order_start_sum_sub_total - $v->total_amount;
			$data[$i]['created_at'] =  $v->created_at;
			$data[$i]['is_credited'] =  $v->is_credited;
			
			$calc['net_amount'][] =  (float)$v->order_start_sum_sub_total;
			
			if(sizeof($v->payments) > 0){
				foreach($v->payments as $p){
					$data[$i]['total_paid'][] = $p->amount;
					if($p->is_paid == 1){
						$calc['paid'][] = (float)$p->amount;
					}
				}
			}
			if(sizeof($v->products) > 0){
				foreach($v->products as $prod){
					if(sizeof($prod->returns) > 0){
						foreach($prod->returns as $r){
							$calc['returns'][] = $r->price * $r->stock;
						}
					}
				}
			}
			
			if($v->is_credited == 1){
				$calc['on_account'][] = (float)$v->amount;
			}else{
				$calc['on_account'][] = 0;
			}
			
			$i++;
		}
		return array_sum($calc['net_amount']) - array_sum($calc['returns']) - array_sum($calc['paid']) - array_sum($calc['on_account']);
	}

	public static function runningBalance($customer_id, $date = ""){
		$payments = CustomerPayment::where('customer_id',$customer_id)
		->whereDate('created_at', '<', $date)->where('is_archived',0)
		->withSum(['products as start_invoice' => function ($q) use($date) {
					$q->where('is_archive', 0)
					->whereDate('created_at', '<', $date);
				}], 'sub_total')
		->get();
		
		if(sizeof($payments) <=0){
			return 0;
		}		
		
		$debt = [];
		$credit = [];
		foreach($payments as $p){
			if($p->credit > 0){
				$credit[] = $p->credit;
			}
			if($p->initiated == 1){
				$debt[] = $p->start_invoice;
			}
			$credit[] = $p->amount;
		}
		/*echo '<pre>';
		print_R($payments->toArray()); 
		print_r($credit); print_r($debt);
		exit;*/
		
		return array_sum($debt) - array_sum($credit);
	}

	public static function runningBalance_V2($customer_id, $date = ""){
		$debt = [];
		$paid = [];
		$credit = [];
		$customer_invoices = CustomerInvoice
			::where('customer_id', $customer_id)
			->withSum(['orderStart as start_invoice' => function ($q) use($date) {
					$q->where('is_archive', 0)->whereDate('created_at', '<', $date);
				}], 'sub_total')
			->with(['payments' =>function($query) use($date){
				return $query->whereDate('created_at', '<', $date)->where('is_archived',0);
				/*$query->withSum(['products as total' => function ($q) {
					$q->where('is_archive', 0);
				}], 'sub_total');*/
			}])
			//->whereDate('created_at', '<', $date)
			//->orWhereDate('updated_at', '<', $date)
			->get();
		
		echo '<pre>'; print_r($customer_invoices->toArray());
		
		foreach($customer_invoices as $ci){
			$debt[] = $ci->start_invoice;
			if(sizeof($ci->payments) > 0){
				foreach($ci->payments as $p){
					$paid[] = $p->amount;
					$credit[] = $p->credit;
				}
			}
		}
		$debt = array_sum($debt);
		$paid = array_sum($paid);
		$credit = array_sum($credit);
		
		return $debt - ($paid + $credit);
	}
	
	public static function unpaidInvoices($customer_id, $params = []){
		$r = CustomerInvoice::query()
			->select('customer_invoices.*')
			->selectSub(function ($query) {
				$query->from('customer_invoice_products')
					->selectRaw('COALESCE(SUM(sub_total), 0)')
					->whereColumn('customer_invoices.id', 'customer_invoice_products.customer_invoice_id')
					//->where('is_archive', 0)
					;
			}, 'total_products')
			->selectSub(function ($query) {
				$query->from('customer_payments')
					->selectRaw('COALESCE(SUM(amount), 0)')
					->whereColumn('customer_invoices.id', 'customer_payments.customer_invoice_id')
					//->where('is_archived', 0)
					//->where('is_discounted', 0)
					//->where('is_refunded', 0)
					//->where('is_credited', 0)
					;
			}, 'total_payments')
			->havingRaw('(total_products - total_payments) > 0')
			->where('customer_id', $customer_id)
			->get();
			
		return $r;
	}
	
	public static function saveRunTimePaymentNormal($customer_id, $amount, $note, $date, $payment_id, $invoices){
		$payments = CustomerInvoice::unpaidInvoices($customer_id, $invoices)->toArray();
		
		if(sizeof($payments) <= 0){
			throw new \Exception('No Invoice Found to Pay.');
		}
		
		usort($payments, function ($a, $b) {
			return $a['balance_due'] <=> $b['balance_due']; // ascending order
		});
		
		// save base.
		$pid = CustomerPayment::create([
			'customer_id' => $customer_id,
			'amount' => $amount,
			'is_runtime_payment' => 1,
			'note' => $note,
			'mode' => "",
			"payment_id" => $payment_id,
			"created_at" => $date . ' ' . now()->format('H:i:s')
		]);
		$calc = $amount;
		foreach($payments as $p){
		
			if(($calc - $p['balance_due']) > 0){
				$pay = $p['balance_due'];
			}else{
				$pay = $calc;
			}
			CustomerPayment::create([
				'customer_id' => $customer_id,
				'amount' => $pay,
				'is_paid' => 1,
				'customer_invoice_id' => $p['id'],
				'customer_payment_id' => $pid->id,
				'mode' => "",
				"payment_id" => $payment_id,
				"created_at" => $date . ' ' . now()->format('H:i:s')
			]);
			$calc = $calc - $p['balance_due'];
		}
		
		return $pid;
	}
	
	public static function saveOnAccountPayment($customer_id, $amount_id, $note, $date, $payment_id, $invoices){
		$payments = CustomerInvoice::unpaidInvoices($customer_id, $invoices)->toArray();
		
		if(sizeof($payments) <= 0){
			throw new \Exception('No Invoice Found to Pay.');
		}
		
		usort($payments, function ($a, $b) {
			return $a['balance_due'] <=> $b['balance_due']; // ascending order
		});
		
		// save base.
		$pid = CustomerPayment::creditedPayments($customer_id, $amount_id);
		
		if(empty($pid)){
			throw new \Exception("Invalid On Account Payment!");
		}
		
		$calc = $pid->remaining_amount; 
		
		if($calc <= 0){
			throw new \Exception("Invalid On Account Balance is Zero!");
		}
		//echo $calc; exit;
		foreach($payments as $p){
		
			if(($calc - $p['balance_due']) > 0){
				$pay = $p['balance_due'];
			}else{
				$pay = $calc;
			}
			CustomerPayment::create([
				'customer_id' => $customer_id,
				'amount' => $pay,
				'is_paid' => 1,
				'customer_invoice_id' => $p['id'],
				'customer_payment_id' => $pid->payment_id,
				'mode' => "",
				"payment_id" => $pid->mode_id,
				"created_at" => $date . ' ' . now()->format('H:i:s')
			]);
			$calc = $calc - $p['balance_due'];
		}
		
		return $pid;
	}
	
	public static function customerInvoicesPayments($customer_id, $startDate, $endDate, $option = ""){
		$invoices = CustomerInvoice::where('customer_id', $customer_id)
		->select('customer_invoices.*')
		->where('status',1)
		->whereDate('created_at', '>=', $startDate)
        ->whereDate('created_at', '<=', $endDate)
		->with(['payments' => function($q){
			$q->where('initiated',0)->with('paymentMode');
		}])
		->selectSub(function ($query) {
			$query->from('customer_invoice_products')
				->selectRaw('COALESCE(SUM(sub_total), 0)')
				->whereColumn('customer_invoices.id', 'customer_invoice_products.customer_invoice_id');
		}, 'total_products')
		->get()
		->map(function ($invoice) {        
			// Example: add new fields
			$invoice->payment_count = $invoice->payments->count();
			//$invoice->formatted_date = $invoice->created_at->format('d-m-Y');
			$invoice->net_amount = $invoice->total_products - $invoice->discount;
			// Sum "amount" from all payments
			
			// Only sum where is_paid = 1
			$invoice->total_paid = $invoice->payments
            ->where('is_paid', 1)
            ->sum(function ($payment) {
                return floatval($payment->amount);
            });
			
			// discounted.
			$invoice->total_discounted = $invoice->payments
            ->where('is_discounted', 1)
            ->sum(function ($payment) {
                return floatval($payment->amount);
            });
			
			// discounted.
			$invoice->total_refunded = $invoice->payments
            ->where('is_refunded', 1)
            ->sum(function ($payment) {
                return floatval($payment->amount);
            });
			
			$totalPaid = 0;
			$paidByCash = 0;
			$paidByCard = 0;
			$paidByCheque = 0;
			$paidByBank = 0;
			foreach ($invoice->payments as $payment) {
				$isPaid = (int) $payment->is_paid;
				if ($isPaid === 1) {
					$amount = (float) $payment->amount;
					$totalPaid += $amount;
					// SAFELY get type
					$type = null;
					if (isset($payment->paymentMode->type)) {
						$type = strtolower($payment->paymentMode->type);
					}
					// Categorize
					if ($type === 'cash') {
						$paidByCash += (float)$amount;
					}
					if ($type === 'Cheque') {
						$paidByCheque += (float)$amount;
					}
					if ($type === 'bank transfer') {
						$paidByBank += (float)$amount;
					}
					if ($type === 'card') {
						$paidByCard += (float)$amount;
					}
					
					//echo $type; 
				}
			}
			$invoice->paid_by_cash = $paidByCash;
			$invoice->paid_by_card = $paidByCard;
			$invoice->paid_by_cheque = $paidByCheque;
			$invoice->paid_by_bank = $paidByBank;
			return $invoice;
		});
		
		return $invoices;
	}
	
}

?>