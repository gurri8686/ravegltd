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

	private function configureMailerFromEnv()
	{
		config([
			'mail.default'                => env('MAIL_MAILER', 'smtp'),
			'mail.mailers.smtp.transport' => 'smtp',
			'mail.mailers.smtp.host'      => env('MAIL_HOST'),
			'mail.mailers.smtp.port'      => (int) env('MAIL_PORT', 587),
			'mail.mailers.smtp.encryption'=> env('MAIL_ENCRYPTION', 'tls'),
			'mail.mailers.smtp.username'  => env('MAIL_USERNAME'),
			'mail.mailers.smtp.password'  => env('MAIL_PASSWORD'),
			'mail.from.address'           => env('MAIL_FROM_ADDRESS'),
			'mail.from.name'              => env('MAIL_FROM_NAME', 'R & A Veg Ltd'),
		]);
		app()->forgetInstance('mailer');
		app()->forgetInstance('swift.mailer');
		app()->forgetInstance('swift.transport');
		\Illuminate\Support\Facades\Mail::clearResolvedInstances();
	}

	public function customerHistory(Request $request, CustomerPayments $customerPayments){
		$rules = [
			'customer_id' => 'required',
			'type' => 'required',
		];
		
		$validator = Validator::make($request->all(), $rules);
		
		if ($validator->fails()) {
			return $this->validationErrorResponse($validator->errors()->messages());
		}
		
		$start_date = $end_date = "";
		extract($request->only('customer_id', 'start_date', 'end_date', 'type', 'invoices'));

		if($type == 'with-balance' && !empty($start_date)){
			$pastBalance = $customerPayments::pastBalance($customer_id, $start_date);
		}else{
			$pastBalance = 0;
		}
		$invoices = $customerPayments::invoicePaymentsHistory($customer_id, $start_date, $end_date);

		if (empty($start_date) || empty($end_date)) {
			$dates = collect($invoices)
				->pluck('created_at')
				->filter()
				->map(function ($d) { return \Carbon\Carbon::parse($d); })
				->values();
			if ($dates->isNotEmpty()) {
				if (empty($start_date)) $start_date = $dates->min()->format('Y-m-d');
				if (empty($end_date)) $end_date = $dates->max()->format('Y-m-d');
			}
		}

		$print = 1;
		return view('pdf.customer-history',compact('pastBalance','invoices','type','customer_id','start_date','end_date','print'));
	}

	public function supplierHistory(Request $request, SupplierPayments $supplierPayments){
		$rules = [
			'supplier_id' => 'required',
			'type' => 'required',
		];
		
		$validator = Validator::make($request->all(), $rules);
		
		if ($validator->fails()) {
			return $this->validationErrorResponse($validator->errors()->messages());
		}
		
		$start_date = $end_date = "";
		extract($request->only('supplier_id', 'start_date', 'end_date', 'type', 'invoices'));

		if($type == 'with-balance' && !empty($start_date)){
			$pastBalance = $supplierPayments::pastBalance($supplier_id, $start_date);
		}else{
			$pastBalance = 0;
		}
		$invoices = $supplierPayments::invoicePaymentsHistory($supplier_id, $start_date, $end_date);

		if (empty($start_date) || empty($end_date)) {
			$dates = collect($invoices)
				->pluck('created_at')
				->filter()
				->map(function ($d) { return \Carbon\Carbon::parse($d); })
				->values();
			if ($dates->isNotEmpty()) {
				if (empty($start_date)) $start_date = $dates->min()->format('Y-m-d');
				if (empty($end_date)) $end_date = $dates->max()->format('Y-m-d');
			}
		}

		$print = 1;
		return view('pdf.supplier-history',compact('pastBalance','invoices','type','supplier_id','start_date','end_date','print'));
	}
	
	public function productHistory(Request $request, SupplierPayments $supplierPayments){
		$rules = [
			'product_id' => 'required',
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
		$dumps = ProductHistory::dumps($product_id, $start_date, $end_date, $supplier_id)->toArray();

		// Auto-fill date range from data when not supplied
		if (empty($start_date) || empty($end_date)) {
			$allDates = collect()
				->merge(collect($suppliers)->pluck('created_at'))
				->merge(collect($supplier_returns)->pluck('created_at'))
				->merge(collect($sales)->pluck('created_at'))
				->merge(collect($customer_returns)->pluck('created_at'))
				->merge(collect($dumps)->pluck('created_at'))
				->filter()
				->map(function ($d) { return \Carbon\Carbon::parse($d); })
				->values();
			if ($allDates->isNotEmpty()) {
				if (empty($start_date)) $start_date = $allDates->min()->format('Y-m-d');
				if (empty($end_date)) $end_date = $allDates->max()->format('Y-m-d');
			}
		}

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
			'to_email'   => 'required|email',
		];

		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			return response()->json(['success' => false, 'payload' => 'Product and recipient email are required']);
		}

		$supplier_id = $product_id = $customer_id = $start_date = $end_date = "";
		extract($request->only('product_id', 'supplier_id', 'customer_id', 'start_date', 'end_date'));

		$product = \App\Models\Product::find($product_id);
		if (!$product) {
			return response()->json(['success' => false, 'payload' => 'Product not found.']);
		}

		$suppliers = ProductHistory::supplierInvoices($product_id, $start_date, $end_date, $supplier_id);
		$supplier_returns = ProductHistory::supplierReturns($product_id, $start_date, $end_date, $supplier_id);
		$sales = ProductHistory::sales($product_id, $start_date, $end_date, $customer_id, $supplier_id);
		$customer_returns = ProductHistory::customerReturns($product_id, $start_date, $end_date, $customer_id)->toArray();
		$dumps = ProductHistory::dumps($product_id, $start_date, $end_date, $supplier_id)->toArray();

		$companyDetails = \App\Models\CompanyDetailModel::first();
		$companyName = $companyDetails->company_name ?? 'R & A Veg Ltd';
		$currency = env('CURRENCY_SYMBOL', '£');
		$productName = $product->name ?? 'Product';

		// Auto-fill dates from data when empty
		if (empty($start_date) || empty($end_date)) {
			$allDates = collect()
				->merge(collect($suppliers)->pluck('created_at'))
				->merge(collect($sales)->pluck('created_at'))
				->filter()
				->map(function ($d) { return \Carbon\Carbon::parse($d); })
				->values();
			if ($allDates->isNotEmpty()) {
				if (empty($start_date)) $start_date = $allDates->min()->format('Y-m-d');
				if (empty($end_date))   $end_date   = $allDates->max()->format('Y-m-d');
			}
		}

		$totalPurchase = collect($suppliers)->sum('sub_total');
		$totalSales    = collect($sales)->sum('sub_total');

		try {
			// Build Excel of product history rows
			$rows = [];
			$pushRows = function ($collection, $type, $partyKey, $qtyKey, $priceKey) use (&$rows) {
				foreach ($collection as $entry) {
					$party = $entry[$partyKey]['name'] ?? '';
					$qty = (float)($entry[$qtyKey] ?? 0);
					$price = (float)($entry[$priceKey] ?? 0);
					$rows[] = [
						$type,
						isset($entry['created_at']) ? uk_ts($entry['created_at'], 'd M Y') : '',
						$entry['product']['name'] ?? '',
						$party,
						$qty,
						number_format($price, 2),
						number_format($qty * $price, 2),
					];
				}
			};
			$pushRows($suppliers,        'Supplier',        'supplier', 'quantity', 'unit_price');
			$pushRows($supplier_returns, 'Supplier Return', 'supplier', 'stock',    'price');
			$pushRows($sales,            'Sale',            'customer', 'quantity', 'unit_price');
			$pushRows($customer_returns, 'Customer Return', 'customer', 'stock',    'price');
			$pushRows($dumps,            'Dump',            'supplier', 'stock',    'price');

			$headings = ['Type','Date','Product','Party','Quantity','Unit Price','Total'];
			$export = new class($rows, $headings) implements
				\Maatwebsite\Excel\Concerns\FromArray,
				\Maatwebsite\Excel\Concerns\WithHeadings {
				protected $rows; protected $headings;
				public function __construct($rows, $headings) { $this->rows = $rows; $this->headings = $headings; }
				public function array(): array { return $this->rows; }
				public function headings(): array { return $this->headings; }
			};
			$excelBinary = \Maatwebsite\Excel\Facades\Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
			$excelName = 'product-history-' . preg_replace('/[^A-Za-z0-9]+/', '-', $productName) . '.xlsx';

			$periodText = (!empty($start_date) && !empty($end_date))
				? \Carbon\Carbon::parse($start_date)->format('d M Y') . ' – ' . \Carbon\Carbon::parse($end_date)->format('d M Y')
				: 'All time';

			$mailData = [
				'company_name'    => $companyName,
				'product_name'    => $productName,
				'subject'         => $request->subject ?? ('Product History: ' . $productName),
				'message'         => $request->message ?? ('Please find attached the product history report for ' . $productName . '.'),
				'cc_email'        => $request->cc_email,
				'period'          => $periodText,
				'total_purchases' => $currency . ' ' . number_format($totalPurchase, 2),
				'total_sales'     => $currency . ' ' . number_format($totalSales, 2),
				'generated_on'    => date('d M Y'),
				'attachment_name' => $excelName,
			];

			$this->configureMailerFromEnv();
			\Illuminate\Support\Facades\Mail::to($request->to_email)
				->send(new \App\Mail\ProductStatementMail($mailData, $excelBinary));

			return response()->json(['success' => true, 'payload' => 'Statement emailed to ' . $request->to_email]);
		} catch (\Exception $ex) {
			return response()->json(['success' => false, 'payload' => $ex->getMessage()]);
		}
	}

	public function productHistoryStatement(Request $request){
		$rules = [
			'product_id' => 'required',
		];

		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			return 'Product is required';
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

	public function stockClosing(Request $request){
		return view('stock_check.print-closing');
	}

	public function products(Request $request){
		return view('product.print');
	}

	public function unassignedSuppliers(Request $request){
		return view('stock_check.print-unassigned');
	}

	public function customerReturn(Request $request){
		return view('stock_check.print-returns', ['type' => 'customer']);
	}

	public function supplierReturn(Request $request){
		return view('stock_check.print-returns', ['type' => 'supplier']);
	}

	public function dump(Request $request){
		return view('stock_check.print-returns', ['type' => 'dump']);
	}

	public function customerReturnable(Request $request){
		return view('stock_check.print-returnable', ['type' => 'customer']);
	}

	public function supplierReturnable(Request $request){
		return view('stock_check.print-returnable', ['type' => 'supplier']);
	}

	public function dumpable(Request $request){
		return view('stock_check.print-returnable', ['type' => 'dump']);
	}
}
