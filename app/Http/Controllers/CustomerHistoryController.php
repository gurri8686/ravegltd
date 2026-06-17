<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lib\Response as CustomResponse;
use Validator;
use DB;
use Auth;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Services\CustomerPayments;
use App\Models\Customer;
use App\Models\CustomerHistoryEmail;
use App\Mail\CustomerStatementMail;
use App\Http\Controllers\Concerns\SendsResendMail;

class CustomerHistoryController extends Controller
{
	use CustomResponse;
	use SendsResendMail;

	/** Company name shown on statements / emails. */
	private function companyName()
	{
		return config('app.company_name', 'R & A Veg Ltd');
	}

	/**
	 * Force the SMTP mailer config at runtime so it does not depend on a
	 * possibly-stale cached .env, then rebuild the mailer.
	 */
	private function configureMailer()
	{
		// Pull from .env so the mailer matches whatever the rest of the app uses.
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

		// Rebuild the mailer so the new config takes effect this request.
		app()->forgetInstance('mailer');
		app()->forgetInstance('swift.mailer');
		app()->forgetInstance('swift.transport');
		\Illuminate\Support\Facades\Mail::clearResolvedInstances();
	}

	/**
	 * Build the statement PDF for a customer + period.
	 * Returns the dompdf instance.
	 */
	private function buildStatementPdf($customerId, $startDate, $endDate, CustomerPayments $customerPayments)
	{
		$customer    = Customer::info($customerId);
		$pastBalance = $customerPayments::pastBalance($customerId, $startDate);
		$invoices    = $customerPayments::invoicePaymentsHistory($customerId, $startDate, $endDate);

		return Pdf::loadView('pdf.customer-statement', [
			'companyName' => $this->companyName(),
			'customer'    => $customer,
			'pastBalance' => $pastBalance,
			'invoices'    => $invoices,
			'start_date'  => $startDate,
			'end_date'    => $endDate,
		]);
	}
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('customer_history.index',[]);
    }
	
	public function customers()
    {
        return $this->successResponse(\App\Models\Customer::getActive());
    }
	
	public function history(Request $request, CustomerPayments $customerPayments){
		try{
			$rules = [
                'currentCustomer' => 'required',
            ];

			$validator = Validator::make($request->all(), $rules);

			if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
			$fromDate = $request->fromDate ?: '2000-01-01';
			$toDate = $request->toDate ?: now()->toDateString();
			$option = $request->option ?: 'all';
			$pastBalance = $customerPayments::pastBalance($request->currentCustomer, $fromDate);

			if ($request->has('page') || $request->has('per_page')) {
				$result = $customerPayments::invoicePaymentsHistory(
					$request->currentCustomer, $fromDate, $toDate, $option,
					[
						'page'     => (int)$request->input('page', 1),
						'per_page' => (int)$request->input('per_page', 10),
						'sort_by'  => $request->input('sort_by', 'created_at'),
						'sort_dir' => $request->input('sort_dir', 'asc'),
						'search'   => (string)$request->input('search', ''),
					]
				);
				return $this->successResponse([
					'past_balance' => $pastBalance,
					'invoices'     => $result['data'] ?? [],
					'totals'       => $result['totals'] ?? null,
					'total_count'  => $result['total_count'] ?? 0,
					'page'         => $result['page'] ?? 1,
					'per_page'     => $result['per_page'] ?? 10,
				]);
			}

			$invoices = $customerPayments::invoicePaymentsHistory($request->currentCustomer, $fromDate, $toDate, $option);
			return $this->successResponse(['past_balance' => $pastBalance, 'invoices' => $invoices]);

		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
	}
	
	public function email(Request $request, CustomerPayments $customerPayments){
		try{
			$rules = [
				'currentCustomer' => 'required',
				'to_email'        => 'required|email',
				'cc_email'        => 'nullable|email',
				'subject'         => 'required|string|max:255',
				'message'         => 'required|string',
			];

			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
				return $this->validationErrorResponse($validator->errors()->messages());
			}

			$customerId = $request->currentCustomer;
			$fromDate   = $request->fromDate ?: '2000-01-01';
			$toDate     = $request->toDate ?: now()->toDateString();

			$customer = Customer::info($customerId);
			if (!$customer) {
				return $this->errorResponse('Customer not found.');
			}

			$invoices = $customerPayments::invoicePaymentsHistory($customerId, $fromDate, $toDate);
			$invoiceCount = 0;
			foreach ($invoices as $inv) {
				if (($inv['is_credited'] ?? 0) != 1) { $invoiceCount++; }
			}
			if ($invoiceCount === 0) {
				return $this->errorResponse('No transactions found in the selected period. Nothing to email.');
			}

			$pastBalance    = $customerPayments::pastBalance($customerId, $fromDate);
			$closingBalance = (float) $pastBalance;
			foreach ($invoices as $inv) {
				if (($inv['is_credited'] ?? 0) != 1) {
					$closingBalance += (float) ($inv['balance'] ?? 0);
				}
			}

			$cur = env('CURRENCY_SYMBOL', '£');

			// Build Excel of invoices for attachment
			$rows = [];
			foreach ($invoices as $inv) {
				if (($inv['is_credited'] ?? 0) == 1) continue;
				$rows[] = [
					$inv['id'] ?? '',
					isset($inv['created_at']) ? uk_ts($inv['created_at'], 'd M Y') : '',
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
			$excelName = 'statement-' . preg_replace('/[^A-Za-z0-9]+/', '-', $customer->name) . '.xlsx';

			$mailData = [
				'company_name'    => $this->companyName(),
				'customer_name'   => $customer->name,
				'subject'         => $request->subject,
				'message'         => $request->message,
				'cc_email'        => $request->cc_email,
				'period'          => Carbon::parse($fromDate)->format('d M Y') . ' – ' . Carbon::parse($toDate)->format('d M Y'),
				'closing_balance' => $cur . ' ' . number_format($closingBalance, 2),
				'generated_on'    => date('d M Y'),
				'attachment_name' => $excelName,
			];

			$log = [
				'customer_id'   => $customerId,
				'to_email'      => $request->to_email,
				'cc_email'      => $request->cc_email,
				'subject'       => $request->subject,
				'period_from'   => $fromDate,
				'period_to'     => $toDate,
				'invoice_count' => $invoiceCount,
				'sent_by'       => Auth::check() ? Auth::user()->id : null,
			];

			try {
				$this->sendMailable($request->to_email, new CustomerStatementMail($mailData, $excelBinary));

				CustomerHistoryEmail::create(array_merge($log, ['status' => 'sent']));

				return $this->successResponse([
					'message' => 'Statement emailed to ' . $request->to_email,
				]);
			} catch (\Exception $mailEx) {
				CustomerHistoryEmail::create(array_merge($log, [
					'status' => 'failed',
					'error'  => $mailEx->getMessage(),
				]));
				return $this->errorResponse('Could not send email: ' . $mailEx->getMessage());
			}

		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
	}

	public function print(Request $request, CustomerPayments $customerPayments){
		$rules = [
			'currentCustomer' => 'required',
		];
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			return $this->validationErrorResponse($validator->errors()->messages());
		}

		$fromDate = $request->fromDate ?: '2000-01-01';
		$toDate   = $request->toDate ?: now()->toDateString();

		$pdf = $this->buildStatementPdf($request->currentCustomer, $fromDate, $toDate, $customerPayments);
		return $pdf->stream('customer-statement.pdf');
	}

	public function statement(Request $request, CustomerPayments $customerPayments){
		$rules = [
			'currentCustomer' => 'required',
		];
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			return $this->validationErrorResponse($validator->errors()->messages());
		}

		$fromDate = $request->fromDate ?: '2000-01-01';
		$toDate   = $request->toDate ?: now()->toDateString();

		$pdf = $this->buildStatementPdf($request->currentCustomer, $fromDate, $toDate, $customerPayments);
		return $pdf->download('customer-statement.pdf');
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
