<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Superadmin management of subscription plan tiers (Basic / Professional /
 * Enterprise …) — price, limits and feature toggles. Stored in the
 * control-plane `plan_tiers` table (organizations connection).
 */
class PlansController extends Controller
{
    /** Feature toggle columns ↔ labels, used by the form and comparison table. */
    public const FEATURES = [
        'f_reports'    => 'Reports',
        'f_multi_user' => 'Multi User',
        'f_analytics'  => 'Advanced Analytics',
        'f_purchases'  => 'Purchases Module',
        'f_customers'  => 'Customers Module',
        'f_suppliers'  => 'Suppliers Module',
    ];

    /** Limit columns ↔ labels (null value = unlimited). */
    public const LIMITS = [
        'user_limit'       => 'Users',
        'product_limit'    => 'Products',
        'customer_limit'   => 'Customers',
        'supplier_limit'   => 'Suppliers',
        'storage_limit_mb' => 'Storage (MB)',
    ];

    public function index()
    {
        $plans = $this->table()->orderBy('sort_order')->orderBy('id')->get();

        return view('admin.plans.index', [
            'plans'    => $plans,
            'features' => self::FEATURES,
            'limits'   => self::LIMITS,
        ]);
    }

    public function create()
    {
        return view('admin.plans.create', ['features' => self::FEATURES, 'limits' => self::LIMITS]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePlan($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $this->table()->insert($data);

        return redirect()->route('admin.plans.index')->with('status', 'Plan created.');
    }

    public function edit($id)
    {
        $plan = $this->findPlan($id);

        return view('admin.plans.edit', ['plan' => $plan, 'features' => self::FEATURES, 'limits' => self::LIMITS]);
    }

    public function update(Request $request, $id)
    {
        $this->findPlan($id);
        $data = $this->validatePlan($request);
        $data['updated_at'] = now();

        $this->table()->where('id', $id)->update($data);

        return redirect()->route('admin.plans.index')->with('status', 'Plan updated.');
    }

    public function toggleStatus(Request $request, $id)
    {
        $plan = $this->findPlan($id);
        $next = $plan->is_active ? 0 : 1;
        $this->table()->where('id', $id)->update(['is_active' => $next, 'updated_at' => now()]);

        $message = 'Plan ' . ($next ? 'activated' : 'deactivated') . '.';
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['is_active' => $next, 'message' => $message]);
        }

        return back()->with('status', $message);
    }

    public function destroy($id)
    {
        $this->findPlan($id);
        $this->table()->where('id', $id)->delete();

        return redirect()->route('admin.plans.index')->with('status', 'Plan deleted.');
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function validatePlan(Request $request): array
    {
        $request->validate([
            'name'             => 'required|string|max:120',
            'price'            => 'required|numeric|min:0',
            'billing_cycle'    => 'required|in:monthly,yearly',
            'currency'         => 'nullable|string|max:8',
            'user_limit'       => 'nullable|integer|min:0',
            'product_limit'    => 'nullable|integer|min:0',
            'customer_limit'   => 'nullable|integer|min:0',
            'supplier_limit'   => 'nullable|integer|min:0',
            'storage_limit_mb' => 'nullable|integer|min:0',
            'sort_order'       => 'nullable|integer',
        ]);

        $data = [
            'name'          => $request->input('name'),
            'price'         => (float) $request->input('price'),
            'billing_cycle' => $request->input('billing_cycle', 'monthly'),
            'currency'      => $request->input('currency') ?: 'GBP',
            'is_active'     => $request->boolean('is_active') ? 1 : 0,
            'sort_order'    => (int) $request->input('sort_order', 0),
        ];

        // limits — blank means unlimited (null)
        foreach (array_keys(self::LIMITS) as $col) {
            $val = $request->input($col);
            $data[$col] = ($val === null || $val === '') ? null : (int) $val;
        }

        // feature toggles
        foreach (array_keys(self::FEATURES) as $col) {
            $data[$col] = $request->boolean($col) ? 1 : 0;
        }

        return $data;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'plan';
        $slug = $base;
        $i = 2;
        while ($this->table()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function table()
    {
        return DB::connection('organizations')->table('plan_tiers');
    }

    private function findPlan($id)
    {
        $plan = $this->table()->where('id', $id)->first();
        abort_if(!$plan, 404);

        return $plan;
    }
}
