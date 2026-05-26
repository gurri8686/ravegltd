<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Superadmin dashboard — the main overview shown right after login.
 *
 * Every metric here is backed by real data (users / subscriptions /
 * customer_invoice_products / customer_payments / activity_log / disk).
 * Two widgets have NO data source in this system (there is no support-ticket
 * module and no server-CPU metric on this host) — they are rendered as honest
 * "not connected" placeholders rather than fabricated numbers.
 */
class DashboardController extends Controller
{
    /** admin and vendor are the same tier (see VendorController). */
    private const ROLES = ['admin', 'vendor'];

    public function index()
    {
        $vendorIds = User::whereIn('role', self::ROLES)->pluck('id');

        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->startOfMonth()->subMonth();

        // ── headline counts ──────────────────────────────────────────────
        $totalVendors    = $vendorIds->count();
        $activeVendors   = User::whereIn('role', self::ROLES)->where('is_active', 1)->count();
        $pendingApproval = User::whereIn('role', self::ROLES)->where('is_active', 0)->count(); // mapped: inactive = pending
        $newSignups      = User::whereIn('role', self::ROLES)->where('created_at', '>=', now()->subDays(30))->count();
        $signupsToday    = User::whereIn('role', self::ROLES)->whereDate('created_at', today())->count();
        $activePct       = $totalVendors > 0 ? round($activeVendors / $totalVendors * 100, 1) : 0;

        // month-over-month vendor growth (real trend chip)
        $vendorsThisMonth = User::whereIn('role', self::ROLES)->where('created_at', '>=', $thisMonth)->count();
        $vendorsLastMonth = User::whereIn('role', self::ROLES)->whereBetween('created_at', [$lastMonth, $thisMonth])->count();
        $vendorTrend      = $this->trend($vendorsThisMonth, $vendorsLastMonth);

        // ── revenue (platform sales, attributed via customer_invoice_products) ──
        $revenue       = $this->salesSum();
        $revenueMonth  = $this->salesSum($thisMonth);
        $revenuePrev   = $this->salesSum($lastMonth, $thisMonth);
        $revenueTrend  = $this->trend($revenueMonth, $revenuePrev);

        // ── platform metrics (superadmin-level only) ──────────────────────
        $totalDomains    = (int) DB::connection('organizations')->table('sites')->count();
        $activeDomains   = (int) DB::connection('organizations')->table('sites')->where('status', 1)->count();
        $verifiedDomains = (int) DB::connection('organizations')->table('sites')->where('dns_verified', 1)->count();
        $avgPerVendor    = $totalVendors > 0 ? $revenue / $totalVendors : 0;

        // newest vendor accounts (replaces the System Health panel)
        $newestVendors = User::whereIn('role', self::ROLES)->orderByDesc('created_at')->limit(6)
            ->get(['first_name', 'last_name', 'email', 'is_active', 'created_at'])
            ->map(fn ($u) => (object) [
                'name'    => trim($u->first_name . ' ' . $u->last_name) ?: $u->email,
                'email'   => $u->email,
                'initial' => strtoupper(substr($u->first_name ?: $u->email, 0, 1)),
                'active'  => (bool) $u->is_active,
                'when'    => $u->created_at ? Carbon::parse($u->created_at)->format('d M Y') : '—',
            ]);

        // ── subscriptions: trial/expiry + distribution ───────────────────
        $today = today();
        $trialExpiring = DB::table('subscriptions')
            ->whereDate('expire', '>=', $today)
            ->whereDate('expire', '<=', $today->copy()->addDays(7))
            ->distinct()->count('vendor_id');

        // latest subscription per vendor → active vs expired; the rest have none
        $latestSubs = DB::table('subscriptions')->orderByDesc('expire')->get()->groupBy('vendor_id');
        $subActive = 0;
        $subExpired = 0;
        foreach ($latestSubs as $rows) {
            $exp = optional($rows->first())->expire;
            if ($exp && Carbon::parse($exp)->gte($today)) {
                $subActive++;
            } else {
                $subExpired++;
            }
        }
        $subNone = max(0, $totalVendors - $latestSubs->count());

        // Note: customer_payments holds almost no real amounts (~£8 total), so
        // there is no reliable "receivables / failed payment" figure to show.

        // ── subscriptions & renewals feed (platform-level, real) ──────────
        // The tenant activity_log (customer/payment/invoice events) is a vendor's
        // own operations, not platform activity — so the superadmin feed tracks
        // subscriptions instead: who's subscribed, and what's expiring.
        $subRows = DB::table('subscriptions as s')
            ->leftJoin('users as u', 'u.id', '=', 's.vendor_id')
            ->orderByDesc('s.expire')->limit(8)
            ->get(['s.start', 's.expire', 'u.first_name', 'u.last_name', 'u.email']);
        $subscriptionFeed = $subRows->map(function ($r) use ($today) {
            $exp = $r->expire ? Carbon::parse($r->expire) : null;
            if (!$exp) {
                $state = 'none';
            } elseif ($exp->lt($today)) {
                $state = 'expired';
            } elseif ($exp->lte($today->copy()->addDays(7))) {
                $state = 'expiring';
            } else {
                $state = 'active';
            }
            $name = trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? '')) ?: ($r->email ?? 'Unknown vendor');

            return (object) [
                'name'    => $name,
                'initial' => strtoupper(substr($name, 0, 1)),
                'period'  => ($r->start ? Carbon::parse($r->start)->format('d M Y') : '—') . '  →  ' . ($exp ? $exp->format('d M Y') : '—'),
                'state'   => $state,
                'when'    => $exp ? $exp->diffForHumans() : '',
            ];
        });

        // ── charts: 12-month revenue + vendor growth ──────────────────────
        $window = collect(range(11, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $chartLabels = $window->map(fn ($d) => $d->format('M'))->values();

        $revByYm = DB::table('customer_invoice_products as cip')
            ->join('customer_invoices as ci', 'ci.id', '=', 'cip.customer_invoice_id')
            ->whereRaw('COALESCE(cip.is_archive,0)=0')
            ->where('ci.created_at', '>=', $window->first())
            ->groupByRaw("DATE_FORMAT(ci.created_at,'%Y-%m')")
            ->selectRaw("DATE_FORMAT(ci.created_at,'%Y-%m') AS ym, ROUND(SUM(cip.sub_total),2) AS total")
            ->pluck('total', 'ym');
        $revenueSeries = $window->map(fn ($d) => (float) ($revByYm[$d->format('Y-m')] ?? 0))->values();

        $growthByYm = User::whereIn('role', self::ROLES)
            ->where('created_at', '>=', $window->first())
            ->groupByRaw("DATE_FORMAT(created_at,'%Y-%m')")
            ->selectRaw("DATE_FORMAT(created_at,'%Y-%m') AS ym, COUNT(*) AS c")
            ->pluck('c', 'ym');
        $growthSeries = $window->map(fn ($d) => (int) ($growthByYm[$d->format('Y-m')] ?? 0))->values();

        return view('admin.dashboard.index', compact(
            'totalVendors', 'activeVendors', 'pendingApproval', 'newSignups', 'signupsToday', 'activePct',
            'revenue', 'revenueMonth', 'revenueTrend', 'vendorTrend', 'vendorsThisMonth', 'trialExpiring',
            'totalDomains', 'activeDomains', 'verifiedDomains', 'avgPerVendor',
            'subActive', 'subExpired', 'subNone', 'newestVendors',
            'subscriptionFeed', 'chartLabels', 'revenueSeries', 'growthSeries'
        ));
    }

    /** Sum of platform sales (customer_invoice_products.sub_total) within an optional date range. */
    private function salesSum(?Carbon $from = null, ?Carbon $to = null): float
    {
        $q = DB::table('customer_invoice_products as cip')
            ->join('customer_invoices as ci', 'ci.id', '=', 'cip.customer_invoice_id')
            ->whereRaw('COALESCE(cip.is_archive,0)=0');
        if ($from) {
            $q->where('ci.created_at', '>=', $from);
        }
        if ($to) {
            $q->where('ci.created_at', '<', $to);
        }

        return (float) $q->sum('cip.sub_total');
    }

    /**
     * Month-over-month percentage change, or null when there's no baseline
     * (so the view can show a neutral chip instead of a misleading "+100%").
     */
    private function trend(float $now, float $prev): ?float
    {
        if ($prev <= 0) {
            return null;
        }

        return round(($now - $prev) / $prev * 100, 1);
    }
}
