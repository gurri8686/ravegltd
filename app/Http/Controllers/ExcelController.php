<?php
/**
 * https://docs.laravel-excel.com/3.1/getting-started/installation.html
 */
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lib\Response as CustomResponse;
use Validator;
use Response;
use Session;
use DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use App\Services\CustomerPayments;
use App\Services\SupplierPayments;

class ExcelController extends Controller
{
    use CustomResponse;
	
	public function customerHistory(Request $request, CustomerPayments $customerPayments){
		$rules = [
			'customer_id' => 'required',
			'start_date' => 'required',
			'end_date' => 'required',
			'type' => 'required',
		];
		
		$validator = Validator::make($request->all(), $rules);
		
		if ($validator->fails()) {
			return $this->validationErrorResponse($validator->errors()->messages());
		}
		
		extract($request->only('customer_id', 'start_date', 'end_date', 'type', 'invoices'));
		
		if($type == 'with-balance'){
			$pastBalance = $customerPayments::pastBalance($customer_id, $start_date);
		}else{
			$pastBalance = 0;
		}
		$invoices = $customerPayments::invoicePaymentsHistory($customer_id, $start_date, $end_date);
		
		// Convert collection to array
		$exportData = collect($invoices)->toArray();

		return Excel::download(
            new class($exportData) implements FromArray {
                protected $data;
                public function __construct($data) { $this->data = $data; }
                public function array(): array { return $this->data; }
            },
            'customer_history-'.$start_date.'~'.$end_date.'.xlsx'
        );
		//$print = 1;
		//return view('pdf.customer-history',compact('pastBalance','invoices','type','customer_id','start_date','end_date','print'));
	}
	
	public function supplierHistory(Request $request, SupplierPayments $supplierPayments){
		$rules = [
			'supplier_id' => 'required',
			'start_date' => 'required',
			'end_date' => 'required',
			'type' => 'required',
		];
		
		$validator = Validator::make($request->all(), $rules);
		
		if ($validator->fails()) {
			return $this->validationErrorResponse($validator->errors()->messages());
		}
		
		extract($request->only('supplier_id', 'start_date', 'end_date', 'type', 'invoices'));
		
		if($type == 'with-balance'){
			$pastBalance = $supplierPayments::pastBalance($supplier_id, $start_date);
		}else{
			$pastBalance = 0;
		}
		$invoices = $supplierPayments::invoicePaymentsHistory($supplier_id, $start_date, $end_date);
		
		// Convert collection to array
		$exportData = collect($invoices)->toArray();

		return Excel::download(
            new class($exportData) implements FromArray {
                protected $data;
                public function __construct($data) { $this->data = $data; }
                public function array(): array { return $this->data; }
            },
            'supplier_history-'.$start_date.'~'.$end_date.'.xlsx'
        );
	}
}
