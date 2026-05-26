<?php

namespace App\Services;
use Illuminate\Http\Request;

use App\Models\SupplierInvoiceProduct;
use App\Models\SupplierPayment;
use App\Models\SupplierInvoice;
use App\Lib\Response as CustomResponse;
use Validator;
use Response;
use Session;
use DB;

class SupplierPayments{
	
	use CustomResponse;
	
	public static function onAccountPayment($data){
		return SupplierPayment::create([
			'supplier_id' => $data['supplier_id'],
			'mode' => 'original',
			'amount' => $data['amount'],
			'note' => $data['note'],
			'is_credited' => 1,
			'created_at' => $data['date'] . ' ' . now()->format('H:i:s'),
			'payment_id' => $data['payment_mode']
		]);
	}
	
	public static function list($id){
		return SupplierPayment::where('supplier_invoice_id',$id)->where('is_archived',0)
		->where('initiated',0)->where('is_paid',1)->orderBy('id','DESC')->get();
	} 
	
	public static function details($id){
		return self::checkAlreadyPaid($id);
	}
	
	public static function onAccountPaymentList($supplier_id){
		return SupplierPayment::where('supplier_id', $supplier_id)
			->where('is_credited',1)->orderBy('id', 'DESC')->get();
	}
	
	public static function checkAlreadyPaid($supplier_invoice_id){
		$payments = SupplierPayment::where('supplier_invoice_id',$supplier_invoice_id)
			->where('is_archived',0)
			->get();
			
		$invoice = SupplierInvoice::where('id',$supplier_invoice_id)->withSum('orderStart','sub_total')->first();
		//print_r($invoice->toArray()); exit;
		
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
		return [
			'total_paid' => (array_sum($total_paid) + array_sum($total_credits)), 
			'paid' => array_sum($total_paid), 
			'total' => $invoice->order_start_sum_sub_total, 
			'type' => $calc < 0 ? 'No Payment Pending / Advance' : 'Paid'];
	}
	
	public static function partialAmountPayable($invoice_id, $amount, $payment_mode, $note = ""){
		$payments = self::checkAlreadyPaid($invoice_id);
		$invoice_main = SupplierInvoice::where('id',$invoice_id)
			//->where('status',1)
			->first();
		$invoice = SupplierInvoiceProduct::where('supplier_invoice_id',$invoice_id)
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
			throw new \Exception('messages.invoice_amount_not_valid');
		}

		$calc = $pending - $amount;
		$credit = 0;
		$debit = 0;
		
		$calc = abs($calc);
		
		//echo $calc; exit;
		//echo $total.'---'. $debit; echo '---'; echo $credit; exit;
		// direct payment based.
		/*$r = SupplierPayment::create([
			'supplier_invoice_id' => $invoice_id,
			'parent_id' => 0,
			'debt' => 0.00,
			'amount' => $amount,
			'is_paid' => 1,
			'credit' => 0.00,
			'supplier_id' => $invoice_main->supplier_id,
			'is_refunded' => 0,
			'payment_id' => $payment_mode,
			'note' => $note,
			'mode' => ($credit >= $total ? 'full' : 'partial')
		]);*/
		
		DB::beginTransaction();
		try{
			// advanced payment based.
			$parent = SupplierPayment::create([
				'supplier_invoice_id' => 0,
				'parent_id' => 0,
				'debt' => 0.00,
				'amount' => $amount,
				'is_runtime_payment' => 1,
				'credit' => 0.00,
				'supplier_id' => $invoice_main->supplier_id,
				'is_refunded' => 0,
				'payment_id' => $payment_mode,
				'note' => $note,
				'mode' => ($credit >= $total ? 'full' : 'partial')
			]);
			$r = SupplierPayment::create([
				'supplier_invoice_id' => $invoice_id,
				'parent_id' => 0,
				'debt' => 0.00,
				'amount' => $amount,
				'is_paid' => 1,
				'credit' => 0.00,
				'supplier_id' => $invoice_main->supplier_id,
				'supplier_payment_id' => $parent->id,
				'is_refunded' => 0,
				'payment_id' => $payment_mode,
				'note' => $note,
				'mode' => ($credit >= $total ? 'full' : 'partial')
			]);
			DB::commit();	
			
		}catch(\Exception $ex){
			DB::rollback();
		}
		
		return $r;
	}
	
	public static function saveRunTimePaymentNormal($supplier_id, $amount, $note, $date, $payment_id, $invoices){
		$payments = SupplierInvoice::unpaidInvoices($supplier_id, $invoices)->toArray();
		
		if(sizeof($payments) <= 0){
			throw new \Exception('No Invoice Found to Pay.');
		}
		
		usort($payments, function ($a, $b) {
			return $a['balance_due'] <=> $b['balance_due']; // ascending order
		});
		
		// save base.
		$pid = SupplierPayment::create([
			'supplier_id' => $supplier_id,
			'amount' => $amount,
			'is_runtime_payment' => 1,
			'note' => $note,
			'mode' => "",
			"payment_id" => $payment_id,
			"created_at" => $date . ' ' . now()->format('H:i:s')
		]);
		$calc = (float)$amount;
		foreach($payments as $p){
			if ($calc <= 0) {
				break;
			}

			$balance = (float)$p['balance_due'];
			$pay = ($calc >= $balance) ? $balance : $calc;

			SupplierPayment::create([
				'supplier_id' => $supplier_id,
				'amount' => $pay,
				'is_paid' => 1,
				'supplier_invoice_id' => $p['id'],
				'supplier_payment_id' => $pid->id,
				'mode' => "",
				"payment_id" => $payment_id,
				"created_at" => $date . ' ' . now()->format('H:i:s')
			]);
			$calc -= $pay;
		}

		return $pid;
	}

	public static function saveOnAccountPayment($supplier_id, $amount_id, $note, $date, $payment_id, $invoices){
		$payments = SupplierInvoice::unpaidInvoices($supplier_id, $invoices)->toArray();
		
		if(sizeof($payments) <= 0){
			throw new \Exception('No Invoice Found to Pay.');
		}
		
		usort($payments, function ($a, $b) {
			return $a['balance_due'] <=> $b['balance_due']; // ascending order
		});
		
		// save base.
		$pid = SupplierPayment::creditedPayments($supplier_id, $amount_id);
		
		if(empty($pid)){
			throw new \Exception("Invalid On Account Payment!");
		}
		
		$calc = (float)$pid->remaining_amount;

		if($calc <= 0){
			throw new \Exception("Invalid On Account Balance is Zero!");
		}
		foreach($payments as $p){
			if ($calc <= 0) {
				break;
			}

			$balance = (float)$p['balance_due'];
			$pay = ($calc >= $balance) ? $balance : $calc;

			SupplierPayment::create([
				'supplier_id' => $supplier_id,
				'amount' => $pay,
				'is_paid' => 1,
				'supplier_invoice_id' => $p['id'],
				'supplier_payment_id' => $pid->payment_id,
				'mode' => "",
				"payment_id" => $payment_id,
				"created_at" => $date . ' ' . now()->format('H:i:s')
			]);
			$calc -= $pay;
		}

		return $pid;
	}
	
	public static function pastBalance($supplier_id, $startDate = ""){
		$invoices = SupplierPayment::where('is_archived', 0)
		->where(function ($q) use ($supplier_id, $startDate) {
			$q->where('initiated', 1)
			  ->where('supplier_id', $supplier_id)
			  //->whereDate('created_at', '>=', $startDate)
			  ->whereDate('created_at', '<', $startDate);
		})
		->orWhere(function ($q) use ($supplier_id, $startDate) {
			$q->where('is_credited', 1)
			  ->where('supplier_id', $supplier_id)
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
		->with(['products' => function ($q) use($supplier_id) {
			$q->where('supplier_id', $supplier_id)->groupBy('supplier_invoice_id');
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
	
	public static function invoicePaymentsHistory($supplier_id, $startDate, $endDate, $option = ''){
		$invoices = SupplierPayment::where('is_archived', 0)
		->where(function ($q) use ($supplier_id, $startDate, $endDate) {
			$q->where('initiated', 1)
			  ->where('supplier_id', $supplier_id);
			if (!empty($startDate)) $q->whereDate('created_at', '>=', $startDate);
			if (!empty($endDate)) $q->whereDate('created_at', '<=', $endDate);
		})
		->orWhere(function ($q) use ($supplier_id, $startDate, $endDate) {
			$q->where('is_credited', 1)
			  ->where('supplier_id', $supplier_id);
			if (!empty($startDate)) $q->whereDate('created_at', '>=', $startDate);
			if (!empty($endDate)) $q->whereDate('created_at', '<=', $endDate);
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
		->with('invoice')
		->with(['products' => function ($q) use($supplier_id) {
			$q->where('supplier_id', $supplier_id)->groupBy('supplier_invoice_id');
			$q->with(['returns']);
		}])
		// only includes the above filtered childPayments
		->withSum('childPayments', 'amount')
		->get();
		
		//return $invoices;
		
		//echo '<pre>'; print_r($invoices->toArray()); exit;
		
		if(sizeof($invoices) <= 0){
			return [];
		}
		
		//echo '<pre>'; print_R($invoices->toArray()); exit;
		
		$data = [];
		$i = 0;
		foreach($invoices as $k => $v){
			$data[$i]['id'] =  $v->supplier_invoice_id;
			//$data[$i]['balance'] =  $v->order_start_sum_sub_total - $v->total_amount;
			$data[$i]['created_at'] =  $v->created_at;
			$data[$i]['is_credited'] =  $v->is_credited;
			$data[$i]['net_amount'] =  (float)$v->order_start_sum_sub_total;
			$data[$i]['total_paid'][] = 0;
			
			$data[$i]['paid_by_card'][] = 0;
			$data[$i]['paid_by_cash'][] = 0;
			$data[$i]['paid_by_cheque'][] = 0;
			$data[$i]['paid_by_bank'][] = 0;

			$data[$i]['delivery_no'] = null;
			$data[$i]['remarks'] = null;

			if(!empty($v->invoice)){
				$data[$i]['other_invoice_id'] = $v->invoice->other_invoice_id;
				$data[$i]['invoice_date'] = $v->invoice->created_at ? date('d M Y', strtotime($v->invoice->created_at)) : null;
				$data[$i]['delivery_no'] = $v->invoice->delivery_no ?? null;
				$data[$i]['remarks'] = $v->invoice->remarks ?? null;
			}
			// Last payment date
			$data[$i]['payment_date'] = null;
			$data[$i]['total'] = (float)$v->order_start_sum_sub_total;
			
			$lastPayDate = null;
			if(sizeof($v->payments) > 0){
				$data[$i]['paid_by_card'][] = $data[$i]['paid_by_cash'][] = $data[$i]['paid_by_cheque'][] = $data[$i]['paid_by_bank'][] = 0;
				foreach($v->payments as $p){
					$data[$i]['total_paid'][] = $p->amount;
					if($p->created_at && (!$lastPayDate || $p->created_at > $lastPayDate)) {
						$lastPayDate = $p->created_at;
					}
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
			if($lastPayDate) $data[$i]['payment_date'] = date('d M Y', strtotime($lastPayDate));
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

		// Filter by payment mode (option) — supports csv for multi-select.
		// cash / cheque / bank transfer = invoices that had a payment of that type.
		// credit = invoices with outstanding balance (not fully paid).
		$optStr = strtolower(trim((string)$option));
		if ($optStr !== '' && $optStr !== 'all') {
			$modes = array_filter(array_map('trim', explode(',', $optStr)));
			if (!empty($modes)) {
				$data = array_values(array_filter($data, function ($row) use ($modes) {
					foreach ($modes as $m) {
						if ($m === 'cash' && (float)($row['paid_by_cash'] ?? 0) > 0) return true;
						if ($m === 'cheque' && (float)($row['paid_by_cheque'] ?? 0) > 0) return true;
						if ($m === 'bank transfer' && (float)($row['paid_by_bank'] ?? 0) > 0) return true;
						if ($m === 'credit' && (float)($row['balance'] ?? 0) > 0) return true;
					}
					return false;
				}));
			}
		}

		return $data;
	}
	
	public static function runningBalance($supplier_id, $date = ""){
		$payments = SupplierPayment::where('supplier_id',$supplier_id)
		->whereDate('created_at', '<', $date)
		->where('is_archived',0)
		->withSum(['products as start_invoice' => function ($q) use($date) {
					$q->where('is_archive', 0)
					->whereDate('created_at', '<', $date);
				}], 'sub_total')
		->get();
		
		//print_r($payments->toArray()); exit;
		
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
	
	public static function invoicePayments($supplier_id, $startDate, $endDate){
		$invoices = $payments = SupplierPayment::selectRaw("
					CASE 
						WHEN supplier_invoice_id = 0 
							THEN CONCAT('id_', id)
						ELSE CONCAT('inv_', supplier_invoice_id)
					END as group_key,
					SUM(amount) as total_debt,
					id,
					is_credited,
					is_refunded,
					is_discounted,
					note,
					SUM(credit) as total_credit,
					SUM(amount) as total_amount,
					MAX(supplier_invoice_id) as supplier_invoice_id,
					MAX(supplier_id) as supplier_id,
					MAX(is_archived) as is_archived,
					MIN(created_at) as created_at
				")
				->with(['payments' => function ($q) {
					$q->where('supplier_invoice_id', '!=', 0);
				}])
				->withSum('childPayments','amount')
				->withSum('orderStart', 'sub_total')
				->where('is_archived', 0)
				->where('supplier_id', $supplier_id)
				->whereDate('created_at', '>=', $startDate)
				->whereDate('created_at', '<=', $endDate)
				->groupBy('group_key')
				->orderBy('id', 'ASC')
				->get()
				;

		
		/*echo '<pre>';
		print_R($payments->toArray()); exit;*/
		
		foreach($invoices as $k => $v){
			$credit = [];
			$debt = [];
			foreach($v->payments as $k2 => $v2){
				$credit[] = $v2->credit;
				$credit[] = $v2->amount;
			}
			if($v->supplier_invoice_id == 0){
				$credit[] = $v->total_amount;
			}
			$invoices[$k]['credit'] = array_sum($credit);
			$invoices[$k]['debt'] = $v->order_start_sum_sub_total - array_sum($credit);
		}
		
		/*echo '<pre>';
		print_r($invoices->toArray()); exit;*/
		
		return $invoices;

	}
	
	public static function supplierInvoicesPayments($supplier_id, $startDate, $endDate){
		$invoices = SupplierInvoice::where('supplier_id', $supplier_id)
		->select('supplier_invoices.*')
		->whereDate('created_at', '>=', $startDate)
        ->whereDate('created_at', '<=', $endDate)
		->withSum(['products as total_products' => function ($q) {
			$q->where('is_archive', 0);
		}], 'sub_total')
		->with(['payments' => function($q){
			$q->where('initiated',0)->with('paymentMode');
		}])
		->get();
		
		//print_r($invoices->toArray()); exit;
		
		$invoices->map(function ($invoice) {        
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
					if ($type === 'cheque') {
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