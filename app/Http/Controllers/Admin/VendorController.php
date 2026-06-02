<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Superadmin management of "vendors". A vendor is a business-account user —
 * and in this system admin and vendor are the same tier, so the panel lists
 * and manages users whose role is 'admin' OR 'vendor' (never Manager /
 * Employee / superadmin). There is no separate vendors table. New accounts
 * are created with the established 'admin' role so they work in the main app
 * immediately (its RBAC/permissions are built around 'admin').
 */
class VendorController extends Controller
{
    /** admin and vendor are treated as the same tier. */
    private const ROLES = ['admin', 'vendor'];

    /** role assigned to brand-new accounts created here. */
    private const CREATE_ROLE = 'admin';

    public function index()
    {
        $vendors = User::whereIn('role', self::ROLES)->orderBy('id', 'desc')->get();
        $ids = $vendors->pluck('id');

        // current (latest) subscription per vendor
        $subs = DB::table('subscriptions')->whereIn('vendor_id', $ids)
            ->orderByDesc('expire')->get()->groupBy('vendor_id');

        // total sales per vendor (attributed via salesman_id) — earnings on the list
        $sales = DB::table('customer_invoice_products as cip')
            ->join('customer_invoices as ci', 'ci.id', '=', 'cip.customer_invoice_id')
            ->whereIn('ci.salesman_id', $ids)->whereRaw('COALESCE(cip.is_archive,0)=0')
            ->groupBy('ci.salesman_id')
            ->selectRaw('ci.salesman_id AS sid, ROUND(SUM(cip.sub_total),2) AS total')->pluck('total', 'sid');

        // linked domain (subdomain) per vendor, from the organizations DB
        $siteIds = $vendors->pluck('site_id')->filter()->unique();
        $domains = $siteIds->isNotEmpty()
            ? DB::connection('organizations')->table('sites')->whereIn('id', $siteIds)->pluck('domain', 'id')
            : collect();

        // plan tiers live in the control-plane (organizations) DB; the current
        // subscription carries the plan_tier_id, resolved to a name here.
        $planNames = DB::connection('organizations')->table('plan_tiers')->pluck('name', 'id');

        foreach ($vendors as $v) {
            $v->subscription = optional($subs->get($v->id))->first();
            $v->sales = (float) ($sales[$v->id] ?? 0);
            $v->domain = $v->site_id ? ($domains[$v->site_id] ?? null) : null;
            $v->plan = ($v->subscription && $v->subscription->plan_tier_id)
                ? ($planNames[$v->subscription->plan_tier_id] ?? null) : null;
        }

        return view('admin.vendors.index', compact('vendors'));
    }

    public function create()
    {
        return view('admin.vendors.create', ['sites' => $this->sites()]);
    }

    /** Vendor "view" = their Sales & Purchases (attributed via salesman_id). */
    public function show($id)
    {
        $vendor = $this->findVendor($id);
        $sid = $vendor->id;

        $sales        = (float) DB::table('customer_invoice_products as cip')
            ->join('customer_invoices as ci', 'ci.id', '=', 'cip.customer_invoice_id')
            ->where('ci.salesman_id', $sid)->whereRaw('COALESCE(cip.is_archive,0)=0')->sum('cip.sub_total');
        $salesInvoices = (int) DB::table('customer_invoices')->where('salesman_id', $sid)->count();
        $salesMonthly  = $this->monthly('customer_invoice_products', 'customer_invoices', 'customer_invoice_id', $sid);

        $purchases        = (float) DB::table('supplier_invoice_products as sip')
            ->join('supplier_invoices as si', 'si.id', '=', 'sip.supplier_invoice_id')
            ->where('si.salesman_id', $sid)->whereRaw('COALESCE(sip.is_archive,0)=0')->sum('sip.sub_total');
        $purchaseInvoices = (int) DB::table('supplier_invoices')->where('salesman_id', $sid)->count();
        $purchasesMonthly = $this->monthly('supplier_invoice_products', 'supplier_invoices', 'supplier_invoice_id', $sid);

        // extra KPIs
        $customers = (int) DB::table('customer_invoices')->where('salesman_id', $sid)->distinct()->count('customer_id');
        $suppliers = (int) DB::table('supplier_invoices')->where('salesman_id', $sid)->distinct()->count('supplier_id');
        $itemsSold = (int) DB::table('customer_invoice_products as cip')
            ->join('customer_invoices as ci', 'ci.id', '=', 'cip.customer_invoice_id')
            ->where('ci.salesman_id', $sid)->whereRaw('COALESCE(cip.is_archive,0)=0')->sum('cip.quantity');
        $lastSale = DB::table('customer_invoices')->where('salesman_id', $sid)->max('created_at');

        // combine sales + purchases per month for a comparison view
        $months = [];
        foreach ($salesMonthly as $m) {
            $months[$m->ym] = (object) ['label' => $m->label, 'sales' => (float) $m->total, 'salesInv' => (int) $m->invoices, 'purchases' => 0.0, 'purchInv' => 0];
        }
        foreach ($purchasesMonthly as $m) {
            if (!isset($months[$m->ym])) {
                $months[$m->ym] = (object) ['label' => $m->label, 'sales' => 0.0, 'salesInv' => 0, 'purchases' => 0.0, 'purchInv' => 0];
            }
            $months[$m->ym]->purchases = (float) $m->total;
            $months[$m->ym]->purchInv = (int) $m->invoices;
        }
        krsort($months);
        $months = array_values($months);

        // ── Domain & Subscription are sub-resources of a vendor (shown as tabs) ──
        // Linked domain (site) lives in the control-plane organizations DB.
        $site = $vendor->site_id
            ? DB::connection('organizations')->table('sites')->where('id', $vendor->site_id)->first()
            : null;

        // This vendor's subscription periods (latest first) + selectable plans.
        $subs = DB::table('subscriptions')->where('vendor_id', $vendor->id)->orderByDesc('expire')->get();
        $plans = DB::connection('organizations')->table('plan_tiers')->where('is_active', 1)
            ->orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'price', 'currency', 'billing_cycle']);
        $planNames = DB::connection('organizations')->table('plan_tiers')->pluck('name', 'id');

        return view('admin.vendors.show', compact(
            'vendor', 'sales', 'salesInvoices', 'purchases', 'purchaseInvoices',
            'customers', 'suppliers', 'itemsSold', 'lastSale', 'months',
            'site', 'subs', 'plans', 'planNames'
        ));
    }

    /** Monthly totals for a products/invoices pair attributed to a salesman. */
    private function monthly(string $products, string $invoices, string $fk, int $salesmanId)
    {
        return DB::table($products . ' as p')
            ->join($invoices . ' as i', 'i.id', '=', 'p.' . $fk)
            ->where('i.salesman_id', $salesmanId)->whereRaw('COALESCE(p.is_archive,0)=0')
            ->groupByRaw("DATE_FORMAT(i.created_at,'%Y-%m')")
            ->selectRaw("DATE_FORMAT(i.created_at,'%Y-%m') AS ym, DATE_FORMAT(i.created_at,'%b %Y') AS label, ROUND(SUM(p.sub_total),2) AS total, COUNT(DISTINCT i.id) AS invoices")
            ->orderByDesc('ym')->get();
    }

    public function store(Request $request)
    {
        $data = $this->validateVendor($request);

        // Optionally provision a brand-new website (subdomain) and link the vendor to it.
        if ($request->input('domain_mode') === 'new' && !empty($data['new_subdomain'])) {
            $data['site_id'] = $this->createSite($data);
        }

        $vendor = new User();
        $this->fill($vendor, $request, $data);
        $vendor->password = Hash::make($data['password']);
        $vendor->role     = self::CREATE_ROLE;
        $vendor->save();

        // Keep the spatie pivot in sync so the main app's RBAC recognizes the
        // account (vendors log into the main app, same tier as admin).
        $vendor->syncRoles([self::CREATE_ROLE]);

        return redirect()->route('admin.vendors.show', $vendor->id)
            ->with('status', 'Vendor created successfully.');
    }

    public function edit($id)
    {
        $vendor = $this->findVendor($id);

        return view('admin.vendors.edit', ['vendor' => $vendor, 'sites' => $this->sites()]);
    }

    public function update(Request $request, $id)
    {
        $vendor = $this->findVendor($id);

        $data = $this->validateVendor($request, $vendor);
        $this->fill($vendor, $request, $data);

        if (!empty($data['password'])) {
            $vendor->password = Hash::make($data['password']);
        }

        // Preserve the account's existing tier role (admin or vendor) and keep
        // the spatie pivot aligned with the column.
        $role = $vendor->role ?: self::CREATE_ROLE;
        $vendor->role = $role;
        $vendor->save();
        $vendor->syncRoles([$role]);

        return redirect()->route('admin.vendors.show', $vendor->id)
            ->with('status', 'Vendor updated successfully.');
    }

    public function toggleStatus(Request $request, $id)
    {
        $vendor = $this->findVendor($id);
        $vendor->is_active = $vendor->is_active ? 0 : 1;
        $vendor->save();

        $message = 'Vendor ' . ($vendor->is_active ? 'activated' : 'deactivated') . '.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['is_active' => (int) $vendor->is_active, 'message' => $message]);
        }

        return back()->with('status', $message);
    }

    public function destroy($id)
    {
        $vendor = $this->findVendor($id);
        $this->deleteImage($vendor->image);
        $vendor->syncRoles([]); // remove pivot rows so nothing is left orphaned
        $vendor->delete();

        return redirect()->route('admin.vendors.index')->with('status', 'User deleted.');
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    private function validateVendor(Request $request, ?User $vendor = null): array
    {
        return $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'email'      => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore(optional($vendor)->id)],
            'username'   => ['nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore(optional($vendor)->id)],
            'phone'      => 'nullable|string|max:30',
            'password'   => ($vendor ? 'nullable' : 'required') . '|string|min:6',
            'country'    => 'nullable|string|max:120',
            'state'      => 'nullable|string|max:120',
            'city'       => 'nullable|string|max:120',
            'zipcode'    => 'nullable|string|max:20',
            'address'    => 'nullable|string|max:500',
            'image'      => 'nullable|image|max:2048',
            'is_active'  => 'nullable|boolean',
            'site_id'    => 'nullable|integer',
            // new-website (subdomain) provisioning — all optional
            'new_subdomain' => 'nullable|string|max:191',
            'new_domain'    => 'nullable|string|max:191',
            'new_database'  => 'nullable|string|max:120',
        ]);
    }

    /** Sites (domains) from the org DB, for the vendor→domain link. */
    private function sites()
    {
        return DB::connection('organizations')->table('sites')->orderBy('domain')->get();
    }

    /**
     * Provision a new website/tenant: insert a sites row (auto API keys) and
     * create its (empty) database. Returns the new site id.
     */
    private function createSite(array $d): int
    {
        $subdomain = trim($d['new_subdomain'] ?? '');
        $domain    = trim(($d['new_domain'] ?? '') ?: $subdomain);
        $database  = trim(($d['new_database'] ?? '') ?: $subdomain);

        $org = DB::connection('organizations');

        $siteId = $org->table('sites')->insertGetId([
            'database'   => $database,
            'subdomain'  => $subdomain,
            'domain'     => $domain,
            'api_key'    => $this->apiKey(),
            'api_secret' => $this->apiKey(),
            'status'     => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // create the (empty) tenant database — ready to be migrated/seeded later
        $safe = str_replace('`', '', $database);
        $org->statement("CREATE DATABASE IF NOT EXISTS `{$safe}`");

        return $siteId;
    }

    /** Generate an API key in the project's NNNN-NNNN-... format. */
    private function apiKey(): string
    {
        return implode('-', array_map(
            fn () => str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
            range(1, 5)
        ));
    }

    /** Assign the shared fields (everything except password/role) onto the model. */
    private function fill(User $vendor, Request $request, array $data): void
    {
        $vendor->first_name = $data['first_name'];
        $vendor->last_name  = $data['last_name'] ?? ''; // column is NOT NULL
        $vendor->email      = $data['email'];
        $vendor->username   = $data['username'] ?: $data['email'];
        $vendor->phone      = $data['phone'] ?? null;
        $vendor->country    = $data['country'] ?? null;
        $vendor->state      = $data['state'] ?? null;
        $vendor->city       = $data['city'] ?? null;
        $vendor->zipcode    = $data['zipcode'] ?? null;
        $vendor->address    = $data['address'] ?? null;
        if (array_key_exists('site_id', $data)) { // only when the form actually sends it
            $vendor->site_id = $data['site_id'];
        }
        $vendor->is_active  = $request->boolean('is_active') ? 1 : 0;

        if ($request->hasFile('image')) {
            $this->deleteImage($vendor->image);
            $file = $request->file('image');
            $name = 'vendor_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $dir  = public_path('uploads/vendors');
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $file->move($dir, $name);
            // stored under the web-served public/ dir so it works across all domains
            $vendor->image = 'uploads/vendors/' . $name;
        }
    }

    /** Delete a vendor image we manage (only our own uploads/ files). */
    private function deleteImage(?string $path): void
    {
        if ($path && str_starts_with($path, 'uploads/') && is_file(public_path($path))) {
            @unlink(public_path($path));
        }
    }

    /**
     * Resolve a vendor by id, guaranteeing its role is in the vendor tier
     * (admin/vendor) so this panel can never touch Managers/Employees/superadmins.
     */
    private function findVendor($id): User
    {
        return User::whereIn('role', self::ROLES)->findOrFail($id);
    }
}
