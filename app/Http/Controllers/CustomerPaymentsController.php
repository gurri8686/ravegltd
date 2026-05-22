<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CustomerPayments;
use App\Lib\Response as CustomResponse;
use App\Models\CustomerPayment;
use App\Models\CustomerCreditUsage;
use Validator;
use App\Models\CustomerInvoice;
use Carbon\Carbon;
use App\Rules\ValidCustomer;
use App\Rules\InvoicesBelongToCustomer;
use App\Rules\AmountCoversInvoices;
use App\Rules\OnAccountCheck;
use DB;

class CustomerPaymentsController extends Controller
{
	use CustomResponse;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, CustomerPayments $customerPayments, $id = null)
    {
        try {
            $list    = $customerPayments::list($id);
            $list->each(function($payment) {
                $payment->credit_used = CustomerCreditUsage::where('customer_payment_id', $payment->id)->sum('amount');
            });
            $details = $customerPayments::details($id);
            return $this->successResponse(['list' => $list, 'details' => $details]);
        } catch (\Exception $ex) {
            return $this->exceptionResponse($ex);
        }
    }

    public function create()
    {
        return view('customer_payments.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, CustomerPayments $customerPayments)
    {
        $cashAmount   = (float)($request->amount ?? 0);
        $creditAmount = (float)($request->creditAmount ?? 0);

        if ($cashAmount <= 0 && $creditAmount <= 0) {
            return $this->validationErrorResponse(['amount' => ['Please enter an amount or apply credit.']]);
        }

        try {
            // Validate credit doesn't exceed available balance
            if ($creditAmount > 0) {
                $available = CustomerCreditUsage::available($request->customer);
                if ($creditAmount > $available + 0.01) {
                    return $this->validationErrorResponse(['creditAmount' => ['Credit amount exceeds available balance of ' . $available]]);
                }
            }

            DB::beginTransaction();

            $lastPayment = null;

            // 1. Cash/Card/Bank/Cheque payment entry (only if cash > 0)
            if ($cashAmount > 0) {
                $lastPayment = $customerPayments::partialAmountPayable(
                    $request->id,
                    $cashAmount,
                    $request->payment_method,
                    $request->note ?? ""
                );
            }

            // 2. Credit payment entry (only if credit applied)
            if ($creditAmount > 0) {
                // Payment mode id 6 = Credit (Cash=2, Cheque=3, Card=4, Bank Transfer=5, Credit=6)
                $creditPayment = $customerPayments::partialAmountPayable(
                    $request->id,
                    $creditAmount,
                    6, // Credit payment mode
                    $request->note ?? ""
                );

                // Record credit usage linked to this credit payment
                CustomerCreditUsage::create([
                    'customer_id'         => $request->customer,
                    'customer_payment_id' => $creditPayment->id,
                    'amount'              => $creditAmount,
                ]);

                $lastPayment = $creditPayment;
            }

            DB::commit();

            return $this->successResponse($lastPayment);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->exceptionResponse($ex);
        }
    }

	public function save(Request $request, CustomerPayments $customerPayments, $customer_id){
		$creditAmount = (float)($request->creditAmount ?? 0);

		$rules = [
			'customer_id' => ['required', 'integer', new ValidCustomer],
			'amount'      => ['required', 'numeric', 'min:0'],
			'paymentMode' => ['required'],
			'date'        => ['required', 'date'],
			'invoices'    => [
				'required', 'array',
				new InvoicesBelongToCustomer($request->customer_id),
				new AmountCoversInvoices($request->customer_id, $request->amount, $creditAmount),
			],
		];

		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			return $this->validationErrorResponse($validator->errors()->messages());
		}

		// Validate credit amount doesn't exceed available balance
		if ($creditAmount > 0) {
			$available = CustomerCreditUsage::available($request->customer_id);
			if ($creditAmount > $available + 0.01) { // small tolerance for float rounding
				return $this->validationErrorResponse(['creditAmount' => ['Credit amount exceeds available balance of ' . $available]]);
			}
		}

		DB::beginTransaction();

		try{
			if ($request->paymentMode == 'on-account') {
				$rules = [
					'onAccount.value.id' => ['required', 'integer', new OnAccountCheck($request->customer_id)]
				];
				$validator = Validator::make($request->all(), $rules);
				if ($validator->fails()) {
					return $this->validationErrorResponse($validator->errors()->messages());
				}
				$r = $customerPayments::saveOnAccountPayment(
					$request->customer_id,
					$request->onAccount['value']['id'],
					$request->has('note') ? $request->note : "",
					$request->date,
					$request->paymentMode,
					$request->invoices
				);
			} else {
				$cashAmount  = (float)$request->amount;
				$totalAmount = $cashAmount + $creditAmount;

				// Distribute total (cash + credit) across selected invoices
				$r = $customerPayments::saveRunTimePaymentNormal(
					$request->customer_id,
					$totalAmount,
					$request->has('note') ? $request->note : "",
					$request->date,
					$request->paymentMode,
					$request->invoices
				);

				// Record credit usage so balance stays accurate
				if ($creditAmount > 0) {
					CustomerCreditUsage::create([
						'customer_id'         => $request->customer_id,
						'customer_payment_id' => $r->id,
						'amount'              => $creditAmount,
					]);
				}
			}

			DB::commit();
			return $this->successResponse($request->invoices);

		} catch (\Exception $ex) {
			DB::rollback();
			return $this->exceptionResponse($ex);
		}
	}

	public function onAccountPayments(Request $request, CustomerPayments $customerPayments, $customer_id){
		return $this->successResponse(
			CustomerPayment::creditedPayments($customer_id)
		);
	}



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        try {
            DB::beginTransaction();

            $payment = \App\Models\CustomerPayment::findOrFail($request->id);

            // 1. Revert credit usage if this payment used return credit.
            //    Deleting the customer_credit_usages row restores the customer's
            //    available balance (available = totalEarned - totalUsed).
            CustomerCreditUsage::where('customer_payment_id', $payment->id)->delete();

            // 2. Delete linked parent payment row (the master "partial" entry
            //    that was created alongside this invoice-linked row).
            if (!empty($payment->customer_payment_id)) {
                \App\Models\CustomerPayment::where('id', $payment->customer_payment_id)->delete();
            }

            // 3. Delete the invoice-linked payment row itself.
            $payment->delete();

            DB::commit();
            return $this->successResponse([]);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->exceptionResponse($ex);
        }
    }

    public function unpaidInvoices(Request $request, CustomerPayments $customerPayments, $customer_id){
        $data = CustomerInvoice::unpaidInvoices($customer_id)->toArray();
        return $this->successResponse(['data' => $data]);
    }
}
