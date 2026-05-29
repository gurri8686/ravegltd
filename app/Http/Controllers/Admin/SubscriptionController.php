<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Vendor subscriptions — listing + history, and adding a subscription period
 * to a vendor. A vendor (user) can have many rows; the latest by `expire` is
 * the current subscription.
 */
class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $vendors = User::whereIn('role', ['admin', 'vendor'])->orderBy('first_name')->get();

        $subscriptions = DB::table('subscriptions as s')
            ->leftJoin('users as u', 'u.id', '=', 's.vendor_id')
            ->orderByDesc('s.expire')
            ->get(['s.id', 's.vendor_id', 's.plan_tier_id', 's.start', 's.expire', 'u.first_name', 'u.last_name', 'u.email']);

        // plan tiers live in the control-plane (organizations) DB — selectable plans
        // for the form, plus an id→name map to label each subscription period.
        $plans = $this->plans();
        $planNames = $plans->pluck('name', 'id');

        $selectedVendor = $request->query('vendor') ? (int) $request->query('vendor') : null;

        return view('admin.subscriptions.index', compact('vendors', 'subscriptions', 'plans', 'planNames', 'selectedVendor'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vendor_id'    => 'required|integer|exists:users,id',
            'plan_tier_id' => 'nullable|integer',
            'start'        => 'required|date',
            'expire'       => 'required|date|after:start',
        ]);

        // guard the plan id against the org-DB plan_tiers (cross-connection, so
        // we can't use the exists: rule which only checks the default connection)
        $planId = $data['plan_tier_id'] ?? null;
        if ($planId && ! $this->plansTable()->where('id', $planId)->exists()) {
            $planId = null;
        }

        DB::table('subscriptions')->insert([
            'vendor_id'    => $data['vendor_id'],
            'plan_tier_id' => $planId,
            'start'        => $data['start'],
            'expire'       => $data['expire'],
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return back()->with('status', 'Subscription added.');
    }

    /** Active plan tiers (organizations DB), ordered for the selector. */
    private function plans()
    {
        return $this->plansTable()->where('is_active', 1)
            ->orderBy('sort_order')->orderBy('id')
            ->get(['id', 'name', 'price', 'currency', 'billing_cycle']);
    }

    private function plansTable()
    {
        return DB::connection('organizations')->table('plan_tiers');
    }

    public function destroy($id)
    {
        DB::table('subscriptions')->where('id', $id)->delete();

        return back()->with('status', 'Subscription removed.');
    }
}
