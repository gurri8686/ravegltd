<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lib\Response as CustomResponse;
use Validator;
use DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Services\SupplierPayments;
use App\Mail\SupplierStatementMail;

class SupplierHistoryController extends Controller
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
		Mail::clearResolvedInstances();
	}
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('supplier_history.index',[]);
    }
	
	public function suppliers()
    {
        return $this->successResponse(\App\Models\Supplier::getActive());
    }
	
	public function history(Request $request, SupplierPayments $supplierPayments){
		try{
			$rules = [
                'currentSupplier' => 'required',
            ];

			$validator = Validator::make($request->all(), $rules);

			if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }

			$fromDate = $request->fromDate ?: '2000-01-01';
			$toDate = $request->toDate ?: now()->toDateString();

			$pastBalance = $supplierPayments::pastBalance($request->currentSupplier, $fromDate);
			$invoices = $supplierPayments::invoicePaymentsHistory($request->currentSupplier, $fromDate, $toDate, $request->option ?: 'all');
			return $this->successResponse(['past_balance' => $pastBalance, 'invoices' => $invoices]);

		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
	}
	
	public function email(Request $request, SupplierPayments $supplierPayments){
		try {
			$rules = [
				'currentSupplier' => 'required',
				'to_email'        => 'required|email',
				'cc_email'        => 'nullable|email',
				'subject'         => 'required|string|max:255',
				'message'         => 'required|string',
			];
			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
				return $this->validationErrorResponse($validator->errors()->messages());
			}

			$fromDate = $request->fromDate ?: '2000-01-01';
			$toDate   = $request->toDate ?: now()->toDateString();

			$supplier = \App\Models\Supplier::info($request->currentSupplier);
			if (!$supplier) {
				return $this->errorResponse('Supplier not found.');
			}

			$invoices = $supplierPayments::invoicePaymentsHistory($request->currentSupplier, $fromDate, $toDate);
			$invoiceCount = 0;
			foreach ($invoices as $inv) {
				if (($inv['is_credited'] ?? 0) != 1) { $invoiceCount++; }
			}
			if ($invoiceCount === 0) {
				return $this->errorResponse('No transactions found in the selected period. Nothing to email.');
			}

			$pastBalance = $supplierPayments::pastBalance($request->currentSupplier, $fromDate);
			$closingBalance = (float) $pastBalance;
			foreach ($invoices as $inv) {
				if (($inv['is_credited'] ?? 0) != 1) {
					$closingBalance += (float) ($inv['balance'] ?? 0);
				}
			}

			$companyDetails = \App\Models\CompanyDetailModel::first();
			$companyName    = $companyDetails->company_name ?? 'R & A Veg Ltd';
			$cur = env('CURRENCY_SYMBOL', '£');

			// Build Excel of supplier invoices
			$rows = [];
			foreach ($invoices as $inv) {
				if (($inv['is_credited'] ?? 0) == 1) continue;
				$rows[] = [
					$inv['id'] ?? '',
					isset($inv['created_at']) ? Carbon::parse($inv['created_at'])->format('d M Y') : '',
					number_format((float)($inv['net_amount'] ?? 0), 2),
					number_format((float)($inv['total_paid'] ?? 0), 2),
					number_format((float)($inv['credit_adj'] ?? 0), 2),
					number_format((float)($inv['total_discounted'] ?? 0), 2),
					number_format((float)($inv['balance'] ?? 0), 2),
				];
			}
			$headings = ['Invoice','Date','Amount','Paid','Credit/Adj','Discount/Adj','Balance'];
			$export = new class($rows, $headings) implements
				\Maatwebsite\Excel\Concerns\FromArray,
				\Maatwebsite\Excel\Concerns\WithHeadings {
				protected $rows; protected $headings;
				public function __construct($rows, $headings) { $this->rows = $rows; $this->headings = $headings; }
				public function array(): array { return $this->rows; }
				public function headings(): array { return $this->headings; }
			};
			$excelBinary = \Maatwebsite\Excel\Facades\Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
			$excelName = 'supplier-statement-' . preg_replace('/[^A-Za-z0-9]+/', '-', $supplier->name) . '.xlsx';

			$mailData = [
				'company_name'    => $companyName,
				'supplier_name'   => $supplier->name,
				'subject'         => $request->subject,
				'message'         => $request->message,
				'cc_email'        => $request->cc_email,
				'period'          => Carbon::parse($fromDate)->format('d M Y') . ' – ' . Carbon::parse($toDate)->format('d M Y'),
				'closing_balance' => $cur . ' ' . number_format($closingBalance, 2),
				'generated_on'    => date('d M Y'),
				'attachment_name' => $excelName,
			];

			try {
				$this->configureMailerFromEnv();
				Mail::to($request->to_email)
					->send(new SupplierStatementMail($mailData, $excelBinary));
				return $this->successResponse(['message' => 'Statement emailed to ' . $request->to_email]);
			} catch (\Exception $mailEx) {
				return $this->errorResponse('Could not send email: ' . $mailEx->getMessage());
			}
		} catch (\Exception $ex) {
			return $this->exceptionResponse($ex);
		}
	}

	public function print(Request $request, SupplierPayments $supplierPayments){
		return $this->errorResponse('Print is handled via /print/supplier_history endpoint.');
	}

	public function statement(Request $request, SupplierPayments $supplierPayments){
		return $this->errorResponse('Statement is handled via /print/supplier_history endpoint.');
	}
	
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
}
