<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestEmail;
use App\Mail\CustomerInvoice;
use \PDF;

class EmailsController extends Controller
{
    public function test(){
		$toEmail = 'harryk.developer@gmail.com'; // Replace with your email
		$subject = 'Laravel Gmail SMTP Test';
		
		$data = ['name' => 'Harry'];
		// This sends the email in the background
		Mail::to($toEmail)->queue(new TestEmail($data));
		return 'Email has been queued!';
	}
	
	public function customerInvoice(Request $request, $invoice)
	{
		$invoiceModel = \App\Models\CustomerInvoice::where('id', $invoice)
			->with(['product', 'order', 'customer'])
			->first();

		if (!$invoiceModel) {
			return response('Invoice not found', 404);
		}

		$companyDetails = \App\Models\CompanyDetailModel::first();

		// Pass only arrays — queue-safe
		$data = [
			'invoice' => $invoiceModel->toArray(),
			'companyDetails' => $companyDetails ? $companyDetails->toArray() : [],
		];

		try {
			Mail::to($data['invoice']['customer']['email'])
				->send(new \App\Mail\CustomerInvoice($data));

			return true;
		} catch (\Exception $ex) {
			return $ex->getMessage();
		}
	}


}
