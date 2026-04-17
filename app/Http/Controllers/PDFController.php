<?php
/**
 * https://github.com/barryvdh/laravel-dompdf
 */
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lib\Response as CustomResponse;
use Validator;
use Response;
use Session;
use DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\CustomerPayments;
use App\Services\SupplierPayments;

class PDFController extends Controller
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
		
		// Load view and generate PDF
		$pdf = Pdf::loadView('pdf.customer-history', [
			'pastBalance' => $pastBalance,
			'invoices'    => $invoices,
			'type'        => $type,
			'supplier_id' => $customer_id,
			'start_date'  => $start_date,
			'end_date'    => $end_date,
		]);

		// Stream PDF in browser
		return $pdf->stream('customer-history.pdf');
	}
	
	public function supplierHistory(Request $request, SupplierPayments $supplierPayments)
	{
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

		if ($type == 'with-balance') {
			$pastBalance = $supplierPayments::pastBalance($supplier_id, $start_date);
		} else {
			$pastBalance = 0;
		}

		$invoices = $supplierPayments::invoicePaymentsHistory(
			$supplier_id,
			$start_date,
			$end_date
		);

		// Load view and generate PDF
		$pdf = Pdf::loadView('pdf.supplier-history', [
			'pastBalance' => $pastBalance,
			'invoices'    => $invoices,
			'type'        => $type,
			'supplier_id' => $supplier_id,
			'start_date'  => $start_date,
			'end_date'    => $end_date,
		]);

		// Stream PDF in browser
		return $pdf->stream('supplier-history.pdf');

		// If you want to download directly:
		// return $pdf->download('supplier-history.pdf');
	}
}
