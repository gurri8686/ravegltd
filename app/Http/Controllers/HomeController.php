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

		// Latest 6 invoices across all dates — used by the mobile "Recent invoices" card.
		// Independent of the selected date filter so the panel always feels useful.
		$recentInvoices = CustomerInvoice::with(['customer', 'product'])
			->orderBy('id', 'desc')
			->limit(6)
			->get();

		// Latest activity stream — mixes payments + new sale invoices + new products into one timeline.
		$recentActivity = self::buildRecentActivity();


		$totalProducts = Product::where('is_active', 1)->count();
		$totalCustomers = Customer::count();
		$totalSuppliers = Supplier::count();
		$latestProducts = Product::where('is_active', 1)->orderBy('updated_at', 'desc')->limit(8)->get();

		// Selected day sales — sum of customer_invoice_products for that day.
		// (We previously joined payments WHERE type='None' which always returns 0 because
		// no payment row has that type. The day's gross sales are simply the day's invoice
		// products, mirroring how $purchaseToday is computed for symmetry.)
		$salesToday = \DB::table('customer_invoice_products')
			->whereDate('updated_at', $selectedDate)
			->sum('sub_total');

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
			'recentInvoices' => $recentInvoices,
			'recentActivity' => $recentActivity,
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
        try {
        $from = $request->get('from', $request->get('date', now()->toDateString()));
        $to   = $request->get('to',   $from);
        // Defensive: ensure $from <= $to
        if (\Carbon\Carbon::parse($from)->gt(\Carbon\Carbon::parse($to))) {
            list($from, $to) = [$to, $from];
        }
        $fromCarbon = \Carbon\Carbon::parse($from);
        $toCarbon   = \Carbon\Carbon::parse($to);
        $isSingleDay = $from === $to;

        $salesValue = \DB::table('customer_invoice_products')
            ->whereDate('updated_at', '>=', $from)
            ->whereDate('updated_at', '<=', $to)
            ->sum('sub_total');

        $purchaseValue = \DB::table('supplier_invoice_products')
            ->where('is_archive', 0)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->sum('sub_total');

        $ordersCount = \DB::table('customer_invoice_orders')
            ->whereDate('updated_at', '>=', $from)
            ->whereDate('updated_at', '<=', $to)
            ->count();

        // Yesterday's sales (relative to $from) — used by the mobile hero card delta display.
        $yesterdayDate = $fromCarbon->copy()->subDay()->toDateString();
        $yesterdayValue = (float) \DB::table('customer_invoice_products')
            ->whereDate('updated_at', $yesterdayDate)
            ->sum('sub_total');

        // 7-day sparkline ending on $to
        $salesChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $toCarbon->copy()->subDays($i);
            $dayTotal = (float) \DB::table('customer_invoice_products')
                ->whereDate('created_at', $d->toDateString())
                ->sum('sub_total');
            $salesChart[] = ['date' => $d->format('d M'), 'amount' => $dayTotal];
        }

        // Recent invoices (latest 6 across all dates) — for the mobile Recent Invoices card.
        // Not filtered by the selected date range so the panel always feels useful.
        $recent = \App\Models\CustomerInvoice::with(['customer', 'product'])
            ->orderBy('id', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($inv) {
                // total = sum of all product sub_totals on this invoice; paid = sum of invoice_payments
                $total = (float) ($inv->product ? $inv->product->sum('sub_total') : 0);
                $paid  = (float) \DB::table('customer_payments')->where('customer_invoice_id', $inv->id)->sum('amount');
                if ($paid <= 0)         { $status = 'Unpaid'; }
                elseif ($paid < $total) { $status = 'Partial'; }
                else                    { $status = 'Paid'; }
                // Friendly date label (Today / Yesterday / 27 May) — time alone (00:00) was meaningless.
                if ($inv->created_at) {
                    $whenC = \Carbon\Carbon::parse($inv->created_at);
                    if ($whenC->isToday())         { $when = 'Today'; }
                    elseif ($whenC->isYesterday()) { $when = 'Yesterday'; }
                    else                            { $when = $whenC->format('d M'); }
                } else { $when = ''; }
                return [
                    'id'         => $inv->id,
                    'invoice_no' => '#INV-' . ($inv->other_invoice_id ?: $inv->id),
                    'customer'   => $inv->customer ? $inv->customer->name : 'Guest',
                    'when'       => $when,
                    'total'      => $total,
                    'paid'       => $paid,
                    'status'     => $status,
                ];
            })
            ->values();

        // Human-friendly label for the cards (e.g. "Today's Sales", "27 Apr Sales", or "27 Apr – 03 May Sales")
        if ($isSingleDay) {
            $label = $fromCarbon->isToday() ? "Today" : $fromCarbon->format('d M');
        } else {
            $label = $fromCarbon->format('d M') . ' – ' . $toCarbon->format('d M');
        }

        // buildRecentActivity is wrapped separately so a failure there doesn't take the whole
        // endpoint down — the rest of the dashboard keeps refreshing.
        try { $activity = self::buildRecentActivity(); } catch (\Throwable $e) { $activity = []; \Log::warning('buildRecentActivity failed: ' . $e->getMessage()); }

        return response()->json([
            'success' => true,
            'payload' => [
                'from'             => $from,
                'to'               => $to,
                'is_single_day'    => $isSingleDay,
                'is_today'         => $isSingleDay && $fromCarbon->isToday(),
                'label'            => $label,
                'sales_value'      => (float) $salesValue,
                'purchase_value'   => (float) $purchaseValue,
                'orders_count'     => (int)   $ordersCount,
                'yesterday_value'  => $yesterdayValue,
                'sales_chart'      => $salesChart,
                'recent_invoices'  => $recent,
                'recent_activity'  => $activity,
                'server_time'      => now()->format('H:i:s'),
            ],
        ]);
        } catch (\Throwable $e) {
            // Surface the real exception in the JSON response so we can see it in the browser console
            // instead of a generic 500. Polling continues — it just keeps getting an error payload until fixed.
            \Log::error('dashboardStats failed: ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'where'   => basename($e->getFile()) . ':' . $e->getLine(),
            ], 500);
        }
    }

    /**
     * Build a mixed real-time activity feed: customer payments (each line = one action with method),
     * new sale invoices, and new products. Ordered by created_at desc. Used by the mobile dashboard.
     */
    private static function buildRecentActivity(int $limit = 6): array
    {
        $items = [];

        // Payment-method lookup (payment_id -> name)
        $payments = \DB::table('payments')->pluck('type', 'id')->toArray();
        $methodMeta = [
            'Cash'          => ['icon' => 'cash',   'color' => '#16a34a'],
            'Card'          => ['icon' => 'card',   'color' => '#1d4ed8'],
            'Bank Transfer' => ['icon' => 'bank',   'color' => '#0e7490'],
            'Cheque'        => ['icon' => 'cheque', 'color' => '#6d28d9'],
            'Credit'        => ['icon' => 'credit', 'color' => 'rgb(234, 88, 12)'],
        ];

        // Latest customer payments — each is a real action (cash received, credit applied, etc.).
        // NOTE: every payment writes TWO rows to customer_payments — one with the invoice id and one
        // aggregate row with customer_invoice_id = 0. Keep only the invoice-linked row so the feed
        // doesn't double-up.
        $cp = \DB::table('customer_payments as cp')
            ->leftJoin('customer_invoices as ci', 'ci.id', '=', 'cp.customer_invoice_id')
            ->leftJoin('customers as c', 'c.id', '=', 'cp.customer_id')
            ->where('cp.is_archived', 0)
            ->where('cp.amount', '>', 0)
            ->where('cp.customer_invoice_id', '>', 0)
            ->orderBy('cp.id', 'desc')
            ->limit($limit)
            ->get([
                'cp.id', 'cp.amount', 'cp.payment_id', 'cp.customer_invoice_id', 'cp.is_credited', 'cp.is_refunded',
                'cp.created_at', 'c.name as customer_name', 'ci.other_invoice_id'
            ]);
        foreach ($cp as $r) {
            $methodName = $payments[$r->payment_id] ?? 'Payment';
            $meta = $methodMeta[$methodName] ?? ['icon' => 'payment', 'color' => '#6b7280'];
            $isCredit = $methodName === 'Credit' || $r->is_credited;
            $pill  = $isCredit ? 'Credit' : ($r->is_refunded ? 'Refund' : $methodName);
            $title = $isCredit ? 'Return credit applied'
                   : ($r->is_refunded ? 'Refund issued' : $methodName . ' payment');
            $invNo = $r->customer_invoice_id ? '#INV-' . ($r->other_invoice_id ?: $r->customer_invoice_id) : '';
            $headline = $r->customer_name ?: 'Customer';
            $items[] = [
                '_ts'      => strtotime($r->created_at),
                'title'    => $title,
                'pill'     => $pill,
                'headline' => $headline,
                'ref'      => $invNo,
                'amount'   => (float) $r->amount,
                'icon'     => $meta['icon'],
                'color'    => $meta['color'],
                'link'     => $r->customer_invoice_id ? ('/data_entry/sales_entry/invoice/' . $r->customer_invoice_id) : null,
            ];
        }

        // Latest sale invoices created
        $inv = \DB::table('customer_invoices as ci')
            ->leftJoin('customers as c', 'c.id', '=', 'ci.customer_id')
            ->orderBy('ci.id', 'desc')
            ->limit($limit)
            ->get(['ci.id', 'ci.other_invoice_id', 'ci.created_at', 'c.name as customer_name']);
        $invIds = $inv->pluck('id')->all();
        $invTotals = !empty($invIds)
            ? \DB::table('customer_invoice_products')
                ->whereIn('customer_invoice_id', $invIds)
                ->select('customer_invoice_id', \DB::raw('SUM(sub_total) as total'))
                ->groupBy('customer_invoice_id')
                ->pluck('total', 'customer_invoice_id')
            : collect();
        foreach ($inv as $r) {
            $invNo = '#INV-' . ($r->other_invoice_id ?: $r->id);
            $items[] = [
                '_ts'      => strtotime($r->created_at),
                'title'    => 'New sale invoice',
                'pill'     => 'New sale',
                'headline' => $r->customer_name ?: 'Customer',
                'ref'      => $invNo,
                'amount'   => (float) ($invTotals[$r->id] ?? 0),
                'icon'     => 'sale',
                'color'    => '#16a34a',
                'link'     => '/data_entry/sales_entry/invoice/' . $r->id,
            ];
        }

        // Latest products added
        $prods = \DB::table('products')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get(['id', 'name', 'created_at']);
        foreach ($prods as $r) {
            $items[] = [
                '_ts'      => strtotime($r->created_at),
                'title'    => 'Product added',
                'pill'     => 'New product',
                'headline' => $r->name ?: 'New product',
                'ref'      => '',
                'amount'   => 0.0,
                'icon'     => 'product',
                'color'    => 'rgb(234, 88, 12)',
                'link'     => '/management/products/view/index',
            ];
        }

        // Sort all by timestamp desc and take top $limit
        usort($items, function ($a, $b) { return $b['_ts'] <=> $a['_ts']; });
        $items = array_slice($items, 0, $limit);

        // Add friendly relative time and drop _ts
        foreach ($items as &$it) {
            $diff = max(0, time() - $it['_ts']);
            if ($diff < 60)               { $when = 'Just now'; }
            elseif ($diff < 3600)         { $when = round($diff / 60) . ' min ago'; }
            elseif ($diff < 86400)        { $when = round($diff / 3600) . ' h ago'; }
            elseif ($diff < 172800)       { $when = 'Yesterday'; }
            else                          { $when = date('d M', $it['_ts']); }
            $it['when'] = $when;
            unset($it['_ts']);
        }
        return $items;
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
