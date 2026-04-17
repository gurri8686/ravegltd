<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CustomerPayments;
use App\Lib\Response as CustomResponse;
use App\Models\CustomerPayment;
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
    public function index($id)
    {
        return $this->successResponse([
			"list" => CustomerPayments::list($id),
			"details" => CustomerPayments::details($id)
		]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
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
        try{
			$rules = [
                'payment_method' => 'required',
				'amount' => 'required',
				'id' => 'required',
				'customer.id' => 'required'
            ];
			
			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
			
			$r = $customerPayments::partialAmountPayable($request->id, 
				$request->amount, 
				$request->payment_method,
				$request->has("note") ? $request->note : ""
			);
			
			return $this->successResponse($r);
			
		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
    }
	
	public function save(Request $request, CustomerPayments $customerPayments, $customer_id){
		//print_r($request->all()); exit;
		$rules = [
			'customer_id' => ['required', 'integer',new ValidCustomer],
			'amount' => ['required','integer'],
			'paymentMode' => ['required'],
			'date' => ['required', 'date'],
			'invoices' => [
				'required', 'array', 
				new InvoicesBelongToCustomer($request->customer_id),
				new AmountCoversInvoices($request->customer_id, $request->amount)
				],
		];
		
		$validator = Validator::make($request->all(), $rules);
		
		if ($validator->fails()) {
			return $this->validationErrorResponse($validator->errors()->messages());
		}
		
		DB::beginTransaction();
		
		try{
			if($request->paymentMode == 'on-account'){
				$rules = [
					'onAccount.value.id' => ['required', 'integer', new OnAccountCheck($request->customer_id)]
				];
				$validator = Validator::make($request->all(), $rules);
				if ($validator->fails()) {
					return $this->validationErrorResponse($validator->errors()->messages());
				}

				// on account payment mode.
				$r = $customerPayments::saveOnAccountPayment(
					$request->customer_id, 
					$request->onAccount['value']['id'], 
					$request->has('note') ? $request->note : "",
					$request->date,
					$request->paymentMode,
					$request->invoices
				);
			}else{
				// normal payment mode.
				$r = $customerPayments::saveRunTimePaymentNormal(
					$request->customer_id, 
					$request->amount, 
					$request->has('note') ? $request->note : "",
					$request->date,
					$request->paymentMode,
					$request->invoices
				);
			}
			
			DB::commit();
			
			return $this->successResponse($request->invoices);
			
		}catch(\Exception $ex){
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
		$rules = [
            'customer_id' => 'required',
			'customer_invoice_id' => 'required',
			'id' => 'required'
		];
		$validator = Validator::make($request->all(), $rules);
		
		if ($validator->fails()) {
			return $this->validationErrorResponse($validator->errors()->messages());
		}
		
		CustomerPayment::where('id', $request->id)
			->where('customer_id',$request->customer_id)
			->where('customer_invoice_id', $request->customer_invoice_id)
			->whereDate('created_at', Carbon::today())
			->update(['is_archived' => 1]);
        
		return $this->successResponse([]);
    }
	
	public function statements(
			\App\Services\SalesPurchaseValidations $validations, 
			\App\Services\CustomerPayments $customerPayments,
			Request $request
		)
		{
		$error = 0;
		$customer = $request->has('customer') ? $request->customer : "";
		$start_date = $request->has('start_date') ? $request->start_date : "";
		$end_date = $request->has('end_date') ? $request->end_date : "";
		
		if(empty($customer) || empty($start_date) || empty($end_date)){
			$error = 1;
		}
		
		//$payments = $customerPayments::invoicePayments($customer,$start_date,$end_date);
		$payments = $customerPayments::invoicePaymentsHistory($customer,$start_date,$end_date);
		
		/*echo '<pre>';
		print_r($payments->toArray()); exit;*/
		
		$balance = $customerPayments::runningBalance($customer, $start_date);
		$customers = \App\Models\Customer::all();

        return view('customer_payments.statements2',compact('error','payments','balance','customers','customer','start_date','end_date'));
    }
	
	public function unpaidInvoices(
			\App\Services\SalesPurchaseValidations $validations, 
			\App\Services\CustomerPayments $customerPayments,
			Request $request, $customer_id){
			
		$search = $request->get('search', '');
        $sortField = $request->get('sortField', 'id');
        $sortOrder = $request->get('sortOrder', 'desc');
        $perPage = $request->get('perPage', 1000);
		
		$query = CustomerInvoice::query();
		
		$query
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
			->where('customer_id', $customer_id);
			
		// search.
		/*if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }*/
		// sort by.
		//$query->orderBy($sortField, $sortOrder);
			
		$payments = $query->paginate($perPage);
		
		//$r = $customerPayments::unpaidInvoices($customer_id);
		
		return $this->successResponse([
            'data' => $payments->items(),
            'total' => $payments->total(),
            'per_page' => $payments->perPage(),
            'current_page' => $payments->currentPage(),
        ]);
		
	}
}
