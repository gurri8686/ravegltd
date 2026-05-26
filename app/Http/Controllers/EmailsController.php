<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Validator;
use App\Mail\TestEmail;

class EmailsController extends Controller
{
	public function test(){
		$toEmail = 'harryk.developer@gmail.com';
		$subject = 'Laravel Gmail SMTP Test';
		$data = ['name' => 'Harry'];
		Mail::to($toEmail)->queue(new TestEmail($data));
		return 'Email has been queued!';
	}

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
		Mail::clearResolvedInstances();
	}

	/**
	 * Customer (Sales) invoice email.
	 * Accepts both GET (legacy) and POST (modal with to_email/subject/message/cc_email).
	 */
	public function customerInvoice(Request $request, $invoice)
	{
		$invoiceModel = \App\Models\CustomerInvoice::where('id', $invoice)
			->with(['product', 'order', 'customer'])
			->first();

		if (!$invoiceModel) {
			return response()->json(['success' => false, 'payload' => 'Invoice not found'], 404);
		}

		$customerEmail = $invoiceModel->customer->email ?? null;
		$toEmail = $request->input('to_email') ?: $customerEmail;
		if (empty($toEmail)) {
			return response()->json([
				'success' => false,
				'payload' => 'Recipient email is required. The customer does not have one on file — please add one or enter an email in the form.',
			]);
		}
		if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
			return response()->json(['success' => false, 'payload' => 'Enter a valid recipient email address.']);
		}
		$ccEmail = $request->input('cc_email');
		if (!empty($ccEmail) && !filter_var($ccEmail, FILTER_VALIDATE_EMAIL)) {
			return response()->json(['success' => false, 'payload' => 'Enter a valid CC email address.']);
		}

		$companyDetails = \App\Models\CompanyDetailModel::first();
		$companyName    = $companyDetails->company_name ?? 'R & A Veg Ltd';
		$customerName   = $invoiceModel->customer->name ?? 'Customer';
		$invoiceDate    = $invoiceModel->created_at ? \Carbon\Carbon::parse($invoiceModel->created_at)->format('d M Y') : '';
		$cur            = env('CURRENCY_SYMBOL', '£');
		$totalAmount    = optional($invoiceModel->order)->total ?? 0;

		$defaultSubject = "Invoice #{$invoiceModel->id} — {$customerName}";
		$defaultMessage = "Dear {$customerName},\n\nPlease find attached your invoice #{$invoiceModel->id} dated {$invoiceDate}.\n\nKindly review and settle the outstanding amount at your earliest convenience.\n\nThank you for your business.";

		$data = [
			'invoice'        => $invoiceModel->toArray(),
			'companyDetails' => $companyDetails ? $companyDetails->toArray() : [],
			'company_name'   => $companyName,
			'customer_name'  => $customerName,
			'invoice_date'   => $invoiceDate,
			'total'          => $cur . ' ' . number_format((float) $totalAmount, 2),
			'subject'        => $request->input('subject') ?: $defaultSubject,
			'message'        => $request->input('message') ?: $defaultMessage,
			'cc_email'       => $ccEmail,
			'pdf_name'       => 'invoice-' . $invoiceModel->id . '.pdf',
			'generated_on'   => date('d M Y'),
		];

		try {
			$this->configureMailerFromEnv();
			Mail::to($toEmail)->send(new \App\Mail\CustomerInvoice($data));
			return response()->json(['success' => true, 'payload' => 'Invoice emailed to ' . $toEmail]);
		} catch (\Exception $ex) {
			return response()->json(['success' => false, 'payload' => 'Could not send email: ' . $ex->getMessage()]);
		}
	}

	/**
	 * Supplier (Purchase) invoice email.
	 */
	public function supplierInvoice(Request $request, $invoice)
	{
		$invoiceModel = \App\Models\SupplierInvoice::where('id', $invoice)
			->with(['product', 'supplier'])
			->first();

		if (!$invoiceModel) {
			return response()->json(['success' => false, 'payload' => 'Invoice not found'], 404);
		}

		$supplierEmail = $invoiceModel->supplier->email ?? null;
		$toEmail = $request->input('to_email') ?: $supplierEmail;
		if (empty($toEmail)) {
			return response()->json([
				'success' => false,
				'payload' => 'Recipient email is required. The supplier does not have one on file — please add one or enter an email in the form.',
			]);
		}
		if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
			return response()->json(['success' => false, 'payload' => 'Enter a valid recipient email address.']);
		}
		$ccEmail = $request->input('cc_email');
		if (!empty($ccEmail) && !filter_var($ccEmail, FILTER_VALIDATE_EMAIL)) {
			return response()->json(['success' => false, 'payload' => 'Enter a valid CC email address.']);
		}

		$companyDetails = \App\Models\CompanyDetailModel::first();
		$companyName    = $companyDetails->company_name ?? 'R & A Veg Ltd';
		$supplierName   = $invoiceModel->supplier->name ?? 'Supplier';
		$invoiceDate    = $invoiceModel->created_at ? \Carbon\Carbon::parse($invoiceModel->created_at)->format('d M Y') : '';
		$cur            = env('CURRENCY_SYMBOL', '£');

		// Compute total from products
		$subtotal = 0;
		foreach ($invoiceModel->product as $p) {
			$subtotal += (float) ($p->sub_total ?? 0);
		}
		$vatPercent = (float) env('VAT', 0);
		$porterage  = (float) env('PORTERAGE', 0);
		$total      = $subtotal + ($subtotal * $vatPercent / 100) + $porterage;

		$defaultSubject = "Purchase Invoice #{$invoiceModel->id} — {$supplierName}";
		$defaultMessage = "Dear {$supplierName},\n\nPlease find attached our purchase invoice #{$invoiceModel->id} dated {$invoiceDate} for your records.\n\nKindly confirm receipt and let us know if you have any questions.\n\nThank you.";

		$data = [
			'invoice'        => $invoiceModel->toArray(),
			'companyDetails' => $companyDetails ? $companyDetails->toArray() : [],
			'company_name'   => $companyName,
			'supplier_name'  => $supplierName,
			'invoice_date'   => $invoiceDate,
			'total'          => $cur . ' ' . number_format($total, 2),
			'subject'        => $request->input('subject') ?: $defaultSubject,
			'message'        => $request->input('message') ?: $defaultMessage,
			'cc_email'       => $ccEmail,
			'pdf_name'       => 'purchase-invoice-' . $invoiceModel->id . '.pdf',
			'generated_on'   => date('d M Y'),
		];

		try {
			$this->configureMailerFromEnv();
			Mail::to($toEmail)->send(new \App\Mail\SupplierInvoice($data));
			return response()->json(['success' => true, 'payload' => 'Purchase invoice emailed to ' . $toEmail]);
		} catch (\Exception $ex) {
			return response()->json(['success' => false, 'payload' => 'Could not send email: ' . $ex->getMessage()]);
		}
	}
}
