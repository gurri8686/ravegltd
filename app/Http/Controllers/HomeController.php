<?php

namespace App\Http\Controllers;
use App\Models\user;
use Illuminate\Http\Request;
use App\Models\CustomerInvoice;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request, \App\Services\DBCountBlocks $dBCountBlocks)
    {
		$selectedDate = $request->get('date', now()->toDateString());
		$selectedCarbon = \Carbon\Carbon::parse($selectedDate);

		$invoices = (new CustomerInvoice)->with(['customer', 'order'])
			->with(['invoicePayment' => function($query){
				return $query->with('payment');
			}])
			->with('products')
			->orderBy('id', 'desc')
			->whereDate('updated_at', $selectedDate)
			->get();

		$totalProducts = Product::where('is_active', 1)->count();
		$totalCustomers = Customer::count();
		$totalSuppliers = Supplier::count();
		$latestProducts = Product::where('is_active', 1)->orderBy('updated_at', 'desc')->limit(8)->get();

		// Selected day sales & purchases
		$salesToday = \DB::table('customer_invoice_products')
			->join('invoice_payments', 'invoice_payments.customer_invoice_id', '=', 'customer_invoice_products.customer_invoice_id')
			->join('payments', 'payments.id', '=', 'invoice_payments.payment_id')
			->where('payments.type', 'None')
			->whereDate('customer_invoice_products.updated_at', $selectedDate)
			->sum('customer_invoice_products.sub_total');

		$purchaseToday = \DB::table('supplier_invoice_products')
			->where('is_archive', 0)
			->whereDate('created_at', $selectedDate)
			->sum('sub_total');

		$todayOrders = \DB::table('customer_invoice_orders')
			->whereDate('updated_at', $selectedDate)
			->count();

		// Last 7 days sales chart ending on selected date
		$salesChart = collect();
		for ($i = 6; $i >= 0; $i--) {
			$date = $selectedCarbon->copy()->subDays($i);
			$dayTotal = \DB::table('customer_invoice_products')
				->whereDate('created_at', $date->toDateString())
				->sum('sub_total');
			$salesChart->push([
				'day' => $date->format('D'),
				'date' => $date->format('d M'),
				'amount' => (float) $dayTotal,
			]);
		}

        return view('home.dashboard-crm',[
			'invoices' => $invoices,
			'blocks' => $dBCountBlocks::dashboard(),
			'totalProducts' => $totalProducts,
			'totalCustomers' => $totalCustomers,
			'totalSuppliers' => $totalSuppliers,
			'latestProducts' => $latestProducts,
			'salesChart' => $salesChart,
			'selectedDate' => $selectedDate,
			'selectedCarbon' => $selectedCarbon,
			'salesToday' => $salesToday,
			'purchaseToday' => $purchaseToday,
			'todayOrders' => $todayOrders,
		]);
    }
    public function chartData(Request $request)
    {
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);
        $daysInMonth = \Carbon\Carbon::create($year, $month)->daysInMonth;
        $data = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = \Carbon\Carbon::create($year, $month, $d);
            $sales = 0;
            $purchases = 0;
            if (!$date->isFuture()) {
                $sales = \DB::table('customer_invoice_products')->whereDate('created_at', $date->toDateString())->sum('sub_total');
                $purchases = \DB::table('supplier_invoice_products')->where('is_archive', 0)->whereDate('created_at', $date->toDateString())->sum('sub_total');
            }
            $data[] = ['day' => $date->format('d'), 'label' => $date->format('D'), 'sales' => (float) $sales, 'purchases' => (float) $purchases, 'future' => $date->isFuture()];
        }
        return response()->json(['success' => true, 'payload' => $data]);
    }

    /**
     * Real-time dashboard stats for a single date (or range start..end).
     * Used by the dashboard date-filter to refresh "Today's" cards without a full page reload.
     */
    public function dashboardStats(Request $request)
    {
        $from = $request->get('from', $request->get('date', now()->toDateString()));
        $to   = $request->get('to',   $from);
        // Defensive: ensure $from <= $to
        if (\Carbon\Carbon::parse($from)->gt(\Carbon\Carbon::parse($to))) {
            [$from, $to] = [$to, $from];
        }
        $fromCarbon = \Carbon\Carbon::parse($from);
        $toCarbon   = \Carbon\Carbon::parse($to);
        $isSingleDay = $from === $to;

        $salesValue = \DB::table('customer_invoice_products')
            ->join('invoice_payments', 'invoice_payments.customer_invoice_id', '=', 'customer_invoice_products.customer_invoice_id')
            ->join('payments', 'payments.id', '=', 'invoice_payments.payment_id')
            ->where('payments.type', 'None')
            ->whereDate('customer_invoice_products.updated_at', '>=', $from)
            ->whereDate('customer_invoice_products.updated_at', '<=', $to)
            ->sum('customer_invoice_products.sub_total');

        $purchaseValue = \DB::table('supplier_invoice_products')
            ->where('is_archive', 0)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->sum('sub_total');

        $ordersCount = \DB::table('customer_invoice_orders')
            ->whereDate('updated_at', '>=', $from)
            ->whereDate('updated_at', '<=', $to)
            ->count();

        // Human-friendly label for the cards (e.g. "Today's Sales", "27 Apr Sales", or "27 Apr – 03 May Sales")
        if ($isSingleDay) {
            $label = $fromCarbon->isToday() ? "Today" : $fromCarbon->format('d M');
        } else {
            $label = $fromCarbon->format('d M') . ' – ' . $toCarbon->format('d M');
        }

        return response()->json([
            'success' => true,
            'payload' => [
                'from'            => $from,
                'to'              => $to,
                'is_single_day'   => $isSingleDay,
                'is_today'        => $isSingleDay && $fromCarbon->isToday(),
                'label'           => $label,
                'sales_value'     => (float) $salesValue,
                'purchase_value'  => (float) $purchaseValue,
                'orders_count'    => (int)   $ordersCount,
            ],
        ]);
    }

    public function noPermission(Request $request)
    {
        return view('error.NoPermission');
    }
	public function workTime(Request $request)
    {
        return view('error.workTime');
    }
    // public function ajaxData(Request $request)
    // {
    //     return 'Hello';
    // }
}
