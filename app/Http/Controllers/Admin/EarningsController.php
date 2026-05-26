<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Per-vendor earnings. Sales are attributed to a vendor via the salesman_id
 * on customer_invoices (the user who made the sale). Each vendor's full
 * monthly history is shown in a popup. (Per-website/domain earnings will be a
 * separate page later — see SiteController.)
 */
class EarningsController extends Controller
{
    private const ROLES = ['admin', 'vendor'];

    public function index()
    {
        $vendors = User::whereIn('role', self::ROLES)->orderBy('id', 'desc')->get();
        $ids = $vendors->pluck('id');

        // attributed sales per salesman
        $sales = DB::table('customer_invoice_products as cip')
            ->join('customer_invoices as ci', 'ci.id', '=', 'cip.customer_invoice_id')
            ->whereIn('ci.salesman_id', $ids)
            ->whereRaw('COALESCE(cip.is_archive,0)=0')
            ->groupBy('ci.salesman_id')
            ->selectRaw('ci.salesman_id AS sid, ROUND(SUM(cip.sub_total),2) AS total')
            ->pluck('total', 'sid');

        // invoice + customer counts per salesman
        $invoices = DB::table('customer_invoices')->whereIn('salesman_id', $ids)
            ->groupBy('salesman_id')->selectRaw('salesman_id AS sid, COUNT(*) AS c')->pluck('c', 'sid');
        $customers = DB::table('customer_invoices')->whereIn('salesman_id', $ids)
            ->groupBy('salesman_id')->selectRaw('salesman_id AS sid, COUNT(DISTINCT customer_id) AS c')->pluck('c', 'sid');

        // full monthly history per salesman
        $monthly = DB::table('customer_invoice_products as cip')
            ->join('customer_invoices as ci', 'ci.id', '=', 'cip.customer_invoice_id')
            ->whereIn('ci.salesman_id', $ids)
            ->whereRaw('COALESCE(cip.is_archive,0)=0')
            ->groupByRaw("ci.salesman_id, DATE_FORMAT(ci.created_at,'%Y-%m')")
            ->selectRaw("ci.salesman_id AS sid, DATE_FORMAT(ci.created_at,'%Y-%m') AS ym, DATE_FORMAT(ci.created_at,'%b %Y') AS label, ROUND(SUM(cip.sub_total),2) AS total, COUNT(DISTINCT ci.id) AS invoices")
            ->orderByDesc('ym')->get()->groupBy('sid');

        foreach ($vendors as $v) {
            $v->sales     = (float) ($sales[$v->id] ?? 0);
            $v->invoices  = (int) ($invoices[$v->id] ?? 0);
            $v->customers = (int) ($customers[$v->id] ?? 0);
            $v->history   = $monthly->get($v->id) ?? collect();
        }

        $totalSales = $vendors->sum('sales');

        return view('admin.earnings.index', compact('vendors', 'totalSales'));
    }
}
