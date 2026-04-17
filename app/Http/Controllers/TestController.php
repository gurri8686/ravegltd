<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Validator;
use Response;
use Session;
use DB;
use App\Lib\Response as CustomResponse;

class TestController extends Controller{
	
	use CustomResponse;
	
	public function index(
		Request $request){
		
		$request->merge(['supplier_invoice_id' => 1]);
		
		$rules = [
			//'customer_invoice_id' => ['required', new \App\Rules\CanDeleteCustomerInvoice()],
			//'customer_invoice_id' => ['required', new \App\Rules\CanDeleteCustomerInvoiceProduct()],
			//'customer_invoice_id' => ['required', new \App\Rules\IsCustomerInvoicePaid()]
			'supplier_invoice_id' => ['required', new \App\Rules\IsSupplierInvoicePaid()]
		];
		
		//echo '<pre>';
		//print_r($request->all());
		
        $validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			return $this->validationErrorResponse($validator->errors()->messages());
		}
		
		var_dump(true);
    }
	
}