<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Superadmin audit log — a filterable view of platform/business activity from
 * the spatie `activity_log` table (model created/updated/deleted events with
 * the acting user). Server-side filtered + paginated (the table is large).
 */
class AuditLogController extends Controller
{
    /** Friendly module names for the logged model classes. */
    private const LABELS = [
        'User'                   => 'User / Vendor',
        'StockProduct'           => 'Stock',
        'Product'                => 'Product',
        'CustomerInvoice'        => 'Customer invoice',
        'CustomerInvoiceProduct' => 'Invoice item',
        'CustomerInvoiceOrder'   => 'Customer order',
        'CustomerPayment'        => 'Customer payment',
        'SupplierInvoice'        => 'Supplier invoice',
        'SupplierInvoiceProduct' => 'Supplier item',
        'SupplierInvoiceOrder'   => 'Supplier order',
        'SupplierPayment'        => 'Supplier payment',
        'Customer'               => 'Customer',
        'Supplier'               => 'Supplier',
    ];

    public function index(Request $request)
    {
        if (!DB::getSchemaBuilder()->hasTable('activity_log')) {
            return view('admin.audit.index', [
                'logs' => collect(), 'modules' => [], 'events' => [], 'users' => collect(),
                'labels' => self::LABELS, 'filters' => [], 'noTable' => true,
            ]);
        }

        $module = $request->query('module');
        $event  = $request->query('event');
        $causer = $request->query('causer');
        $from   = $request->query('from');
        $to     = $request->query('to');
        $search = $request->query('q');

        $query = DB::table('activity_log')->whereNotNull('subject_type')
            ->when($module, fn ($q) => $q->where('subject_type', $module))
            ->when($event, fn ($q) => $q->where('event', $event))
            ->when($causer, fn ($q) => $q->where('causer_id', (int) $causer))
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->when($search, fn ($q) => $q->where('description', 'like', '%' . $search . '%'))
            ->orderByDesc('id');

        $logs = $query->paginate(30)->withQueryString();

        // resolve acting users for the rows on this page
        $causers = User::whereIn('id', collect($logs->items())->pluck('causer_id')->filter()->unique())
            ->get(['id', 'first_name', 'last_name', 'email'])->keyBy('id');

        $logs->getCollection()->transform(function ($r) use ($causers) {
            $base = class_basename($r->subject_type);
            $r->module_label = self::LABELS[$base] ?? ucfirst(strtolower(trim(preg_replace('/(?<!^)[A-Z]/', ' $0', $base))));
            $u = $r->causer_id ? $causers->get($r->causer_id) : null;
            $r->who = $u ? (trim($u->first_name . ' ' . $u->last_name) ?: $u->email) : 'System';
            $r->when_human = $r->created_at ? Carbon::parse($r->created_at)->diffForHumans() : '';
            $r->when_exact = $r->created_at ? Carbon::parse($r->created_at)->format('d M Y H:i') : '';

            return $r;
        });

        // filter dropdown options
        $modules = DB::table('activity_log')->select('subject_type')->whereNotNull('subject_type')
            ->distinct()->pluck('subject_type')
            ->mapWithKeys(fn ($t) => [$t => self::LABELS[class_basename($t)] ?? class_basename($t)])->sort();

        $events = DB::table('activity_log')->select('event')->whereNotNull('event')->distinct()->pluck('event');

        $userIds = DB::table('activity_log')->select('causer_id')->whereNotNull('causer_id')->distinct()->pluck('causer_id');
        $users = User::whereIn('id', $userIds)->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']);

        return view('admin.audit.index', [
            'logs'    => $logs,
            'modules' => $modules,
            'events'  => $events,
            'users'   => $users,
            'labels'  => self::LABELS,
            'filters' => compact('module', 'event', 'causer', 'from', 'to', 'search'),
            'noTable' => false,
        ]);
    }
}
