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
            ->get(['s.id', 's.vendor_id', 's.start', 's.expire', 'u.first_name', 'u.last_name', 'u.email']);

        $selectedVendor = $request->query('vendor') ? (int) $request->query('vendor') : null;

        return view('admin.subscriptions.index', compact('vendors', 'subscriptions', 'selectedVendor'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vendor_id' => 'required|integer|exists:users,id',
            'start'     => 'required|date',
            'expire'    => 'required|date|after:start',
        ]);

        DB::table('subscriptions')->insert([
            'vendor_id'  => $data['vendor_id'],
            'start'      => $data['start'],
            'expire'     => $data['expire'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Subscription added.');
    }

    public function destroy($id)
    {
        DB::table('subscriptions')->where('id', $id)->delete();

        return back()->with('status', 'Subscription removed.');
    }
}
