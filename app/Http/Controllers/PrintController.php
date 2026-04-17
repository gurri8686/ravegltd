<?php

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
use App\Services\ProductHistory;

class PrintController extends Controller
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
		$print = 1;
		return view('pdf.customer-history',compact('pastBalance','invoices','type','customer_id','start_date','end_date','print'));
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
		$print = 1;
		return view('pdf.supplier-history',compact('pastBalance','invoices','type','supplier_id','start_date','end_date','print'));
	}
	
	public function productHistory(Request $request, SupplierPayments $supplierPayments){
		$rules = [
			'product_id' => 'required',
			'start_date' => 'required',
			'end_date' => 'required',
			'type' => 'required',
		];
		
		$validator = Validator::make($request->all(), $rules);
		
		if ($validator->fails()) {
			return $this->validationErrorResponse($validator->errors()->messages());
		}
		
		$supplier_id = $product_id = $customer_id = $start_date = $type = $end_date = "";
		extract($request->only('product_id', 'supplier_id', 'customer_id', 'start_date', 'end_date', 'type'));
		
		$suppliers = ProductHistory::supplierInvoices($product_id, $start_date, $end_date, $supplier_id);
		$supplier_returns = ProductHistory::supplierReturns($product_id, $start_date, $end_date, $supplier_id);
		$sales = ProductHistory::sales($product_id, $start_date, $end_date, $customer_id, $supplier_id);
		$customer_returns = ProductHistory::customerReturns($product_id, $start_date, $end_date, $customer_id)->toArray();
		//print_R($customer_returns->toArray()); exit;
		$dumps = ProductHistory::dumps($product_id, $start_date, $end_date, $supplier_id)->toArray();
		$print = 1;
		$companyDetails = \App\Models\CompanyDetailModel::first();
		$product = \App\Models\Product::find($product_id);
		return view('pdf.product-history',compact('supplier_id','start_date', 'end_date', 'type',
			'suppliers','supplier_returns','sales','customer_returns','dumps','print','companyDetails','product'
		));
	}

	public function productHistoryEmail(Request $request){
		$rules = [
			'product_id' => 'required',
			'start_date' => 'required',
			'end_date' => 'required',
		];

		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			return response()->json(['success' => false, 'payload' => 'Product and date range are required']);
		}

		$supplier_id = $product_id = $customer_id = $start_date = $end_date = "";
		extract($request->only('product_id', 'supplier_id', 'customer_id', 'start_date', 'end_date'));

		$product = \App\Models\Product::find($product_id);
		$suppliers = ProductHistory::supplierInvoices($product_id, $start_date, $end_date, $supplier_id);
		$supplier_returns = ProductHistory::supplierReturns($product_id, $start_date, $end_date, $supplier_id);
		$sales = ProductHistory::sales($product_id, $start_date, $end_date, $customer_id, $supplier_id);
		$customer_returns = ProductHistory::customerReturns($product_id, $start_date, $end_date, $customer_id)->toArray();
		$dumps = ProductHistory::dumps($product_id, $start_date, $end_date, $supplier_id)->toArray();

		$companyDetails = \App\Models\CompanyDetailModel::first();
		$currency = env('CURRENCY_SYMBOL', '$');
		$productName = $product->name ?? 'Product';

		$totalPurchase = collect($suppliers)->sum('sub_total');
		$totalSales = collect($sales)->sum('sub_total');

		try {
			$toEmail = $companyDetails->email ?? config('mail.from.address');
			$body = "<h3>Product History Report: {$productName}</h3>
				<p>Period: {$start_date} to {$end_date}</p>
				<p>Total Purchases: {$currency}" . number_format($totalPurchase, 2) . "</p>
				<p>Total Sales: {$currency}" . number_format($totalSales, 2) . "</p>
				<p>Supplier Returns: " . count(collect($supplier_returns)->toArray()) . " records</p>
				<p>Customer Returns: " . count($customer_returns) . " records</p>
				<p>Dumps: " . count($dumps) . " records</p>";

			\Illuminate\Support\Facades\Mail::raw('', function ($message) use ($toEmail, $productName, $start_date, $end_date, $body) {
				$message->to($toEmail)
					->subject("Product History: {$productName} ({$start_date} to {$end_date})")
					->setBody($body, 'text/html');
			});
			return response()->json(['success' => true, 'payload' => 'Email sent successfully!']);
		} catch (\Exception $ex) {
			return response()->json(['success' => false, 'payload' => $ex->getMessage()]);
		}
	}

	public function productHistoryStatement(Request $request){
		$rules = [
			'product_id' => 'required',
			'start_date' => 'required',
			'end_date' => 'required',
		];

		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			return 'Product and date range are required';
		}

		$supplier_id = $product_id = $customer_id = $start_date = $end_date = "";
		extract($request->only('product_id', 'supplier_id', 'customer_id', 'start_date', 'end_date'));

		$product = \App\Models\Product::find($product_id);
		$suppliers = ProductHistory::supplierInvoices($product_id, $start_date, $end_date, $supplier_id);
		$supplier_returns = ProductHistory::supplierReturns($product_id, $start_date, $end_date, $supplier_id);
		$sales = ProductHistory::sales($product_id, $start_date, $end_date, $customer_id, $supplier_id);
		$customer_returns = ProductHistory::customerReturns($product_id, $start_date, $end_date, $customer_id)->toArray();
		$dumps = ProductHistory::dumps($product_id, $start_date, $end_date, $supplier_id)->toArray();

		$companyDetails = \App\Models\CompanyDetailModel::first();
		$currency = env('CURRENCY_SYMBOL', '$');
		$productName = $product->name ?? 'Product';

		$html = view('pdf.product-history-statement', compact(
			'suppliers', 'supplier_returns', 'sales', 'customer_returns', 'dumps',
			'start_date', 'end_date', 'companyDetails', 'currency', 'productName'
		))->render();

		$pdf = Pdf::loadHTML($html);
		$pdf->setPaper('A4', 'landscape');

		return $pdf->stream("product-history-statement-{$start_date}-to-{$end_date}.pdf");
	}
}
