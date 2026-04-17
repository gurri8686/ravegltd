<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Response;
use Session;
use DB;
use Illuminate\Support\Facades\Mail;
use App\Models\CustomerInvoice;
use App\Models\SupplierInvoice;
use Auth;
use App\Lib\Response as CustomResponse;
use \PDF;
use Carbon\Carbon;
use Redirect;

class ReportActionsController extends Controller
{
    use CustomResponse;
	
	public $customerInvoices;
	
	public $customer;
	
	public $companyDetails;
	
	public $supplierInvoices;
	
	public $errors;
	
	public function customerInvoices($options){
		$rules = [
			'customer_id' => 'required',
			'start_date' => 'required',
			'end_date' => 'required'
		];
		$validator = Validator::make($options, $rules);
		
		if ($validator->fails()) {
			$this->errors = $this->validationErrorResponse($validator->errors()->messages());
			return $this->errors;
		}
		
		$query = CustomerInvoice::where('customer_id',$options['customer_id'])
					->with('customer')
					->with('products')
					->with('salesman')
					->with('invoicePayment')
					->whereDate('created_at', '>=', $options['start_date'])
                    ->whereDate('created_at', '<=', $options['end_date']);
					
		if( isset($options['ids']) && is_array($options['ids']) ){
			$query = $query->whereIn('id', $options['ids']);
		}
		
		$this->customerInvoices = $query->get();
		$this->companyDetails = \App\Models\CompanyDetailModel::first();
		$this->customer = \App\Models\Customer::find($options['customer_id']);
	}
	
	public function supplierInvoices($options){
		$rules = [
			'supplier_id' => 'required',
			'start_date' => 'required',
			'end_date' => 'required'
		];
		$validator = Validator::make($options, $rules);
		
		if ($validator->fails()) {
			$this->errors = $this->validationErrorResponse($validator->errors()->messages());
			return $this->errors;
		}
		
		$query = SupplierInvoice::where('supplier_id',$options['supplier_id'])
					->with('supplier')
					->with('order')
					->with('products')
					->with('salesman')
					->whereDate('created_at', '>=', $options['start_date'])
                    ->whereDate('created_at', '<=', $options['end_date'])
					;
					
		if( isset($options['ids']) && is_array($options['ids']) ){
			$query = $query->whereIn('id', $options['ids']);
		}
		
		$this->supplierInvoices = $query->get();
	}
		
	public function customerCommonAction(Request $request, $type){
		$this->customerInvoices($request->all());
		
		if(!empty($this->errors)){
			return $this->errors;
		}
		
		try{
			switch($type){
				case 'download_all':
					$pdf = PDF::loadView('report-actions.customer-common',[
						'invoices' => $this->customerInvoices, 
						'company' => $this->companyDetails,
						'customer' => $this->customer,
						'type' => $type
					]);
					//return $pdf->stream('invoice.pdf');
					return $pdf->download('invoice.pdf');
					break;
				
				case 'download_selected':
					$pdf = PDF::loadView('report-actions.customer-common',[
						'invoices' => $this->customerInvoices, 
						'company' => $this->companyDetails,
						'customer' => $this->customer,
						'type' => $type
					]);
					//return $pdf->stream('invoice.pdf');
					return $pdf->download('invoice.pdf');
					break;
				
				case 'print_all':
					return view('report-actions.customer-common',
						[
							'invoices' => $this->customerInvoices, 
							'company' => $this->companyDetails,
							'customer' => $this->customer,
							'type' => $type
						]
					);
					break;
				
				case 'print_selected':
					return view('report-actions.customer-common',
						[
							'invoices' => $this->customerInvoices, 
							'company' => $this->companyDetails,
							'customer' => $this->customer,
							'type' => $type
						]
					);
					break;
				
				
				case 'email':
				
				case 'statement':
					
			}
		
			
			
		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
	}
	
	public function supplierCommonAction(Request $request, $type){
		$this->supplierInvoices($request->all());
		
		if(!empty($this->errors)){
			return $this->errors;
		}
		
		try{
			return view('report-actions.supplier-common',
				[
					'invoices' => $this->supplierInvoices, 
					'company' => $this->companyDetails,
					'type' => $type
				]
			);
			
		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
	}
}
