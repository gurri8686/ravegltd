<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Superadmin analytics — platform KPIs and trend charts, all from real data
 * (sales via customer_invoice_products, vendors via users, subscriptions).
 */
class AnalyticsController extends Controller
{
    private const ROLES = ['admin', 'vendor'];

    public function index()
    {
        $vendorIds   = User::whereIn('role', self::ROLES)->pluck('id');
        $totalVendors = $vendorIds->count();
        $activeVendors = User::whereIn('role', self::ROLES)->where('is_active', 1)->count();

        $revenue = (float) DB::table('customer_invoice_products as cip')
            ->join('customer_invoices as ci', 'ci.id', '=', 'cip.customer_invoice_id')
            ->whereRaw('COALESCE(cip.is_archive,0)=0')->sum('cip.sub_total');
        $avgPerVendor = $totalVendors > 0 ? $revenue / $totalVendors : 0;

        // subscription distribution + "conversion" (subscribed / total)
        $today = today();
        $latestSubs = DB::table('subscriptions')->orderByDesc('expire')->get()->groupBy('vendor_id');
        $subActive = 0; $subExpired = 0;
        foreach ($latestSubs as $rows) {
            $exp = optional($rows->first())->expire;
            ($exp && Carbon::parse($exp)->gte($today)) ? $subActive++ : $subExpired++;
        }
        $subNone = max(0, $totalVendors - $latestSubs->count());
        $conversion = $totalVendors > 0 ? round($subActive / $totalVendors * 100, 1) : 0;

        // 12-month series
        $window = collect(range(11, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $labels = $window->map(fn ($d) => $d->format('M'))->values();

        $revByYm = DB::table('customer_invoice_products as cip')
            ->join('customer_invoices as ci', 'ci.id', '=', 'cip.customer_invoice_id')
            ->whereRaw('COALESCE(cip.is_archive,0)=0')->where('ci.created_at', '>=', $window->first())
            ->groupByRaw("DATE_FORMAT(ci.created_at,'%Y-%m')")
            ->selectRaw("DATE_FORMAT(ci.created_at,'%Y-%m') AS ym, ROUND(SUM(cip.sub_total),2) AS total")->pluck('total', 'ym');
        $revenueSeries = $window->map(fn ($d) => (float) ($revByYm[$d->format('Y-m')] ?? 0))->values();

        $growthByYm = User::whereIn('role', self::ROLES)->where('created_at', '>=', $window->first())
            ->groupByRaw("DATE_FORMAT(created_at,'%Y-%m')")
            ->selectRaw("DATE_FORMAT(created_at,'%Y-%m') AS ym, COUNT(*) AS c")->pluck('c', 'ym');
        $growthSeries = $window->map(fn ($d) => (int) ($growthByYm[$d->format('Y-m')] ?? 0))->values();
        // cumulative vendors over the window
        $running = $totalVendors - array_sum($growthSeries->all());
        $cumulativeSeries = $growthSeries->map(function ($n) use (&$running) { $running += $n; return $running; })->values();

        // top vendors by sales
        $topSales = DB::table('customer_invoice_products as cip')
            ->join('customer_invoices as ci', 'ci.id', '=', 'cip.customer_invoice_id')
            ->whereIn('ci.salesman_id', $vendorIds)->whereRaw('COALESCE(cip.is_archive,0)=0')
            ->groupBy('ci.salesman_id')->selectRaw('ci.salesman_id AS sid, ROUND(SUM(cip.sub_total),2) AS total')
            ->orderByDesc('total')->limit(6)->get();
        $userMap = User::whereIn('id', $topSales->pluck('sid'))->get()->keyBy('id');
        $topVendors = $topSales->map(fn ($r) => (object) [
            'name'  => optional($userMap->get($r->sid)) ? (trim($userMap->get($r->sid)->first_name . ' ' . $userMap->get($r->sid)->last_name) ?: $userMap->get($r->sid)->email) : ('#' . $r->sid),
            'total' => (float) $r->total,
        ]);

        return view('admin.analytics.index', compact(
            'revenue', 'activeVendors', 'totalVendors', 'avgPerVendor', 'conversion',
            'labels', 'revenueSeries', 'growthSeries', 'cumulativeSeries',
            'subActive', 'subExpired', 'subNone', 'topVendors'
        ));
    }
}
