<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Superadmin view of "vendors" as tenant businesses (sites). Each site has its
 * own database, a subscription (plans: start/expire) in the org DB, and
 * earnings computed live from its own database.
 */
class SiteController extends Controller
{
    public function index()
    {
        $sites = $this->org()->table('sites')->orderBy('id')->get()->map(function ($site) {
            $site->stats = $this->siteStats($site->database);
            return $site;
        });

        return view('admin.earnings.index', compact('sites'));
    }

    public function show($id)
    {
        $site = $this->org()->table('sites')->where('id', $id)->first();
        abort_if(!$site, 404);

        $site->stats = $this->siteStats($site->database);

        return view('admin.earnings.show', compact('site'));
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    /** Org (control-plane) connection holding sites + plans. */
    private function org()
    {
        return DB::connection('organizations');
    }

    /** Live earnings/stats from a site's own database (null if unreachable). */
    private function siteStats(?string $database): ?array
    {
        if (!$database) {
            return null;
        }

        try {
            config(['database.connections._site' => array_merge(
                config('database.connections.mysql'),
                ['database' => $database]
            )]);
            DB::purge('_site');
            $c = DB::connection('_site');

            return [
                'sales'     => (float) $c->table('customer_invoice_products')->whereRaw('COALESCE(is_archive,0)=0')->sum('sub_total'),
                'purchases' => (float) $c->table('supplier_invoice_products')->whereRaw('COALESCE(is_archive,0)=0')->sum('sub_total'),
                'invoices'  => (int) $c->table('customer_invoices')->count(),
                'customers' => (int) $c->table('customers')->count(),
                'products'  => (int) $c->table('products')->count(),
                'suppliers' => (int) $c->table('suppliers')->count(),
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }
}
