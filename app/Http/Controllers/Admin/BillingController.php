<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Superadmin billing overview. Revenue figures, recent invoices and
 * subscription records are REAL (from sales / customer_invoices /
 * subscriptions). "Outstanding" and "Failed payments" require a payment
 * gateway that isn't wired yet, so they're shown as honest placeholders.
 */
class BillingController extends Controller
{
    public function index()
    {
        $now = now();

        $total   = $this->sales();
        $month   = $this->sales($now->copy()->startOfMonth());
        $year    = $this->sales($now->copy()->startOfYear());

        // 12-month revenue series
        $window = collect(range(11, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $labels = $window->map(fn ($d) => $d->format('M'))->values();
        $byYm = DB::table('customer_invoice_products as cip')
            ->join('customer_invoices as ci', 'ci.id', '=', 'cip.customer_invoice_id')
            ->whereRaw('COALESCE(cip.is_archive,0)=0')->where('ci.created_at', '>=', $window->first())
            ->groupByRaw("DATE_FORMAT(ci.created_at,'%Y-%m')")
            ->selectRaw("DATE_FORMAT(ci.created_at,'%Y-%m') AS ym, ROUND(SUM(cip.sub_total),2) AS total")->pluck('total', 'ym');
        $revenueSeries = $window->map(fn ($d) => (float) ($byYm[$d->format('Y-m')] ?? 0))->values();
        $windowTotal = $revenueSeries->sum(); // total over the charted 12-month window

        // recent invoices (real — total = sum of their products)
        $invoices = DB::table('customer_invoices as ci')
            ->join('customer_invoice_products as cip', 'cip.customer_invoice_id', '=', 'ci.id')
            ->leftJoin('customers as c', 'c.id', '=', 'ci.customer_id')
            ->whereRaw('COALESCE(cip.is_archive,0)=0')
            ->groupBy('ci.id', 'ci.created_at', 'ci.status', 'c.name')
            ->selectRaw('ci.id, ci.created_at, ci.status, c.name AS customer, ROUND(SUM(cip.sub_total),2) AS total')
            ->orderByDesc('ci.created_at')->limit(12)->get();

        // subscription records (real)
        $subs = DB::table('subscriptions as s')->leftJoin('users as u', 'u.id', '=', 's.vendor_id')
            ->orderByDesc('s.expire')->limit(10)
            ->get(['s.start', 's.expire', 'u.first_name', 'u.last_name', 'u.email']);
        $today = today();
        $subscriptions = $subs->map(function ($r) use ($today) {
            $exp = $r->expire ? Carbon::parse($r->expire) : null;
            return (object) [
                'vendor' => trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? '')) ?: ($r->email ?? '—'),
                'start'  => $r->start ? Carbon::parse($r->start)->format('d M Y') : '—',
                'expire' => $exp ? $exp->format('d M Y') : '—',
                'active' => $exp && $exp->gte($today),
            ];
        });

        return view('admin.billing.index', compact('total', 'month', 'year', 'windowTotal', 'labels', 'revenueSeries', 'invoices', 'subscriptions'));
    }

    private function sales(?Carbon $from = null): float
    {
        $q = DB::table('customer_invoice_products as cip')
            ->join('customer_invoices as ci', 'ci.id', '=', 'cip.customer_invoice_id')
            ->whereRaw('COALESCE(cip.is_archive,0)=0');
        if ($from) {
            $q->where('ci.created_at', '>=', $from);
        }

        return (float) $q->sum('cip.sub_total');
    }
}
