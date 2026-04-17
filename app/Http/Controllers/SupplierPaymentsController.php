<?php

namespace App\Http\Controllers;
use App\Models\SupplierInvoice;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\SupplierPayments;
use App\Lib\Response as CustomResponse;
use Validator;
use DB;
use Illuminate\Http\Request;

class SupplierPaymentsController extends Controller
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
			"list" => SupplierPayments::list($id),
			"details" => SupplierPayments::details($id)
		]);
    }
	
	public function indexOnAccount(Request $request, SupplierPayments $supplierPayments){
		$rules = [
			'supplier_id' => ['required','integer']
		];
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			$errors = $validator->errors();
			return $this->errorResponse(json_encode($errors));
		}
        return $this->successResponse($supplierPayments::onAccountPaymentList($request->supplier_id));
	}

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('supplier_payments.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, SupplierPayments $supplierPayments)
    {
        try{
			$rules = [
                'payment_method' => 'required',
				'amount' => 'required',
				'id' => 'required',
				'supplier.id' => 'required'
            ];
			
			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
			
			$r = SupplierPayments::partialAmountPayable($request->id, 
				$request->amount, 
				$request->payment_method,
				$request->has("note") ? $request->note : ""
			);
			
			return $this->successResponse($r);
			
		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
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
    public function destroy($id)
    {
        //
    }
	
	public function unpaidInvoices(Request $request, $supplier_id){
		$search = $request->get('search', '');
        $sortField = $request->get('sortField', 'id');
        $sortOrder = $request->get('sortOrder', 'desc');
        $perPage = $request->get('perPage', 1000);
		
		$query = SupplierInvoice::query();
		
		$query
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
	
	public function save(Request $request, SupplierPayments $supplierPayments, $supplier_id){
		//print_r($request->all()); exit;
		$rules = [
			'supplier_id' => ['required', 'integer',new \App\Rules\ValidSupplier],
			'amount' => ['required','integer'],
			'paymentMode' => ['required'],
			'date' => ['required', 'date'],
			'invoices' => [
				'required', 'array', 
				new \App\Rules\InvoicesBelongToSupplier($request->supplier_id),
				new \App\Rules\AmountCoversSupplierInvoices($request->supplier_id, $request->amount)
				],
		];
		
		$validator = Validator::make($request->all(), $rules);
		
		if ($validator->fails()) {
			return $this->validationErrorResponse($validator->errors()->messages());
		}
		
		//print_r($request->all()); exit;
		DB::beginTransaction();
		
		try{
			if($request->paymentMode == 'on-account'){
				$rules = [
					'onAccount.value.id' => ['required', 'integer', new \App\Rules\OnAccountCheckSupplier($request->supplier_id)]
				];
				$validator = Validator::make($request->all(), $rules);
				if ($validator->fails()) {
					return $this->validationErrorResponse($validator->errors()->messages());
				}

				// on account payment mode.
				$r = $supplierPayments::saveOnAccountPayment(
					$request->supplier_id, 
					$request->onAccount['value']['id'], 
					$request->has('note') ? $request->note : "",
					$request->date,
					$request->paymentMode,
					$request->invoices
				);
			}else{
				// normal payment mode.
				$r = $supplierPayments::saveRunTimePaymentNormal(
					$request->supplier_id, 
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
	
	public function onAccountPayments(Request $request,SupplierPayments $supplierPayments, $supplier_id){
		return $this->successResponse(
			SupplierPayment::creditedPayments($supplier_id)
		);
	}
	
	public function listSuppliers(Request $request){
		$s = Supplier::select(['id','name','supplier_id'])->where('is_active',1)->get();
		return $this->successResponse($s);
	}
	
	public function createOnAccount(Request $request){
		return view('supplier_payments.on-account');
	}
	
	public function storeOnAccount(Request $request, SupplierPayments $supplierPayments){
		$rules = [
			'payment_mode' => ['required', 'integer'],
			'amount' => ['required', 'integer'],
			'date' => ['required','date'],
			'supplier_id' => ['required','integer']
		];
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			$errors = $validator->errors();
			return $this->errorResponse(json_encode($errors));
		}
		
		$r = $supplierPayments::onAccountPayment($request->all());
		return $this->successResponse($r);
	}
	
	public function statements(
			SupplierPayments $supplierPayments,
			Request $request
		)
		{
		$error = 0;
		$supplier = $request->has('supplier') ? $request->supplier : "";
		$start_date = $request->has('start_date') ? $request->start_date : "";
		$end_date = $request->has('end_date') ? $request->end_date : "";
		
		if(empty($supplier) || empty($start_date) || empty($end_date)){
			$error = 1;
		}
		
		$payments = $supplierPayments::invoicePayments($supplier,$start_date,$end_date);
		
		$balance = $supplierPayments::runningBalance($supplier, $start_date);
		
		/*echo '<pre>';
		print_R($balance);
		print_r($payments->toArray()); exit;*/
		
		$suppliers = \App\Models\Supplier::all();

        return view('supplier_payments.statements',compact('error','payments','balance','suppliers','supplier','start_date','end_date'));
    }
	
	
}
