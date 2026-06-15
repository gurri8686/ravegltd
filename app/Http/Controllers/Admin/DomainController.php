<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TenantProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Superadmin domain & subdomain management. Operates on the control-plane
 * `sites` table (organizations DB) — one row per tenant business, each with a
 * subdomain, an optional custom domain, its own database and status.
 *
 * SSL status and DNS verification are NOT stored guesses: the "Verify" action
 * performs a real DNS lookup and a real TLS handshake, then caches the result.
 */
class DomainController extends Controller
{
    /** Subdomain labels that must never be handed to a tenant. */
    private const RESERVED = [
        'admin', 'api', 'app', 'mail', 'www', 'ftp', 'smtp', 'imap', 'pop',
        'ns1', 'ns2', 'cdn', 'static', 'assets', 'blog', 'dev', 'staging',
        'test', 'root', 'system', 'support', 'help', 'status', 'login',
        'register', 'secure', 'ssl', 'vpn', 'db', 'sql', 'mysql', 'webmail',
        'portal', 'dashboard', 'billing', 'pay', 'payment',
    ];

    /** Default base domain when auto-generating a subdomain (TENANT_BASE_DOMAIN overrides). */
    private const BASE_DOMAIN = 'ravegltd.com';

    /** Configured tenant base domain, falling back to the default constant. */
    private function baseDomain(): string
    {
        return config('tenant.base_domain') ?: self::BASE_DOMAIN;
    }

    public function index()
    {
        // Show only real vendor domains: those with their OWN provisioned
        // database. Hide the platform/main domain and any domain still on the
        // main shared DB, plus anything linked to a superadmin.
        $mainDb = config('database.connections.mysql.database');
        $superadminSiteIds = User::where('role', 'superadmin')->whereNotNull('site_id')->pluck('site_id')->all();

        $sites = $this->org()->table('sites')
            ->where('database', '!=', $mainDb)
            ->when($superadminSiteIds, fn ($q) => $q->whereNotIn('id', $superadminSiteIds))
            ->orderByDesc('id')->get();

        // map each site to its linked vendor (only admin/vendor users, not superadmin)
        $vendors = User::whereIn('role', ['admin', 'vendor'])->whereNotNull('site_id')
            ->get(['id', 'first_name', 'last_name', 'email', 'site_id'])->groupBy('site_id');
        foreach ($sites as $s) {
            $linked = $vendors->get($s->id);
            $first = optional($linked)->first();
            $s->vendor = $first ? (trim($first->first_name . ' ' . $first->last_name) ?: $first->email) : null;
            $s->vendor_more = $linked ? max(0, $linked->count() - 1) : 0;
        }

        return view('admin.domains.index', ['sites' => $sites, 'reserved' => self::RESERVED]);
    }

    public function create()
    {
        return view('admin.domains.create', ['vendors' => $this->vendorOptions(), 'base' => $this->baseDomain()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateDomain($request);
        $vendorId = (int) $request->input('vendor_id');

        // Database-per-tenant: when a vendor is linked we ALWAYS provision their
        // dedicated database (create DB + clone schema + RBAC + admin user) and
        // derive its name ourselves — the form's database field is ignored for
        // linked vendors. If the vendor already has one, we reuse it.
        $database = null;
        if ($vendorId && ($vendor = User::find($vendorId))) {
            try {
                $provisioner = new TenantProvisioner();
                $dbName = $provisioner->databaseName($vendor);
                $database = $this->databaseExists($dbName) ? $dbName : $provisioner->provisionForVendor($vendor);
            } catch (\Throwable $e) {
                return back()->withInput()->withErrors([
                    'subdomain' => 'Could not provision the vendor database: ' . $e->getMessage(),
                ]);
            }
        } else {
            $database = $data['database'] ?? null;
        }

        $id = $this->org()->table('sites')->insertGetId([
            'subdomain'  => $data['subdomain'],
            'domain'     => $data['domain'] ?: $data['subdomain'],
            'database'   => $database ?: str_replace('.', '_', $data['subdomain']),
            'api_key'    => $this->apiKey(),
            'api_secret' => $this->apiKey(),
            'status'     => $request->boolean('status') ? 1 : 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->linkVendor($request, $id);

        return redirect()->route('admin.domains.index')
            ->with('status', 'Domain created' . ($database ? " — database provisioned: {$database}" : '') . '.');
    }

    /** Does a database with this name already exist on the server? */
    private function databaseExists(string $name): bool
    {
        return DB::table('information_schema.schemata')->where('schema_name', $name)->exists();
    }

    public function edit($id)
    {
        $site = $this->findSite($id);
        $linkedVendorId = optional(User::where('site_id', $id)->first())->id;

        return view('admin.domains.edit', [
            'site' => $site, 'vendors' => $this->vendorOptions($linkedVendorId), 'base' => $this->baseDomain(),
            'linkedVendorId' => $linkedVendorId,
        ]);
    }

    public function update(Request $request, $id)
    {
        $site = $this->findSite($id);
        $data = $this->validateDomain($request, $id);

        $this->org()->table('sites')->where('id', $id)->update([
            'subdomain'  => $data['subdomain'],
            'domain'     => $data['domain'] ?: $data['subdomain'],
            'database'   => $data['database'] ?: $site->database,
            'status'     => $request->boolean('status') ? 1 : 0,
            'updated_at' => now(),
        ]);

        $this->linkVendor($request, $id);

        return redirect()->route('admin.domains.index')->with('status', 'Domain updated.');
    }

    public function toggleStatus(Request $request, $id)
    {
        $site = $this->findSite($id);
        $next = $site->status ? 0 : 1;
        $this->org()->table('sites')->where('id', $id)->update(['status' => $next, 'updated_at' => now()]);

        $message = 'Domain ' . ($next ? 'activated' : 'deactivated') . '.';
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => $next, 'message' => $message]);
        }

        return back()->with('status', $message);
    }

    public function destroy($id)
    {
        $this->findSite($id);
        // unlink any vendors first so nothing points at a missing site
        User::where('site_id', $id)->update(['site_id' => null]);
        $this->org()->table('sites')->where('id', $id)->delete();

        return redirect()->route('admin.domains.index')->with('status', 'Domain removed. (Its database was not dropped.)');
    }

    /** Run a real DNS + SSL check for the site's public host and cache it. */
    public function verify(Request $request, $id)
    {
        $site = $this->findSite($id);
        $host = $this->host($site->domain ?: $site->subdomain);

        $dns = $this->checkDns($host);
        $ssl = $this->checkSsl($host);

        $this->org()->table('sites')->where('id', $id)->update([
            'dns_verified' => $dns ? 1 : 0,
            'ssl_status'   => $ssl,
            'verified_at'  => now(),
            'updated_at'   => now(),
        ]);

        return response()->json([
            'dns_verified' => $dns,
            'ssl_status'   => $ssl,
            'verified_at'  => uk_ts(now(), 'd M Y H:i'),
            'message'      => 'Checked ' . $host,
        ]);
    }

    // ── helpers ───────────────────────────────────────────────────────────

    private function validateDomain(Request $request, $ignoreId = null): array
    {
        $data = $request->validate([
            'subdomain' => ['required', 'string', 'max:200', 'regex:/^[a-z0-9.-]+$/i'],
            'domain'    => ['nullable', 'string', 'max:200', 'regex:/^[a-z0-9.-]+$/i'],
            'database'  => ['nullable', 'string', 'max:200'],
            'vendor_id' => ['nullable', 'integer'],
        ], [
            'subdomain.regex' => 'Subdomain may only contain letters, numbers, dots and hyphens.',
            'domain.regex'    => 'Custom domain may only contain letters, numbers, dots and hyphens.',
        ]);

        $sub  = strtolower(trim($data['subdomain']));
        $label = explode('.', $sub)[0];

        // reserved-name blacklist (first label)
        if (in_array($label, self::RESERVED, true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'subdomain' => "\"{$label}\" is a reserved name and cannot be used.",
            ]);
        }

        // prevent duplicate subdomain / domain
        $clash = $this->org()->table('sites')
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where(function ($q) use ($sub, $data) {
                $q->where('subdomain', $sub);
                if (!empty($data['domain'])) {
                    $q->orWhere('domain', strtolower(trim($data['domain'])));
                }
            })->exists();
        if ($clash) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'subdomain' => 'That subdomain or custom domain is already in use.',
            ]);
        }

        // normalize optional keys so callers never hit an undefined index
        $data['subdomain'] = $sub;
        $data['domain']    = !empty($data['domain']) ? strtolower(trim($data['domain'])) : null;
        $data['database']  = $data['database'] ?? null;

        return $data;
    }

    /** Point a vendor (user) at this site, clearing any previous holder. */
    private function linkVendor(Request $request, int $siteId): void
    {
        $vendorId = (int) $request->input('vendor_id');
        if (!$vendorId) {
            return;
        }
        User::where('site_id', $siteId)->update(['site_id' => null]);
        User::where('id', $vendorId)->update(['site_id' => $siteId]);
    }

    /**
     * Vendors selectable for a domain: only admin/vendor accounts that don't
     * already have a domain (site_id is null). On edit, the currently-linked
     * vendor is included so it stays selected.
     */
    private function vendorOptions(?int $includeId = null)
    {
        return User::whereIn('role', ['admin', 'vendor'])
            ->where(function ($q) use ($includeId) {
                $q->whereNull('site_id');
                if ($includeId) {
                    $q->orWhere('id', $includeId);
                }
            })
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email']);
    }

    private function checkDns(string $host): bool
    {
        if ($host === '') {
            return false;
        }
        try {
            return checkdnsrr($host, 'A') || checkdnsrr($host, 'AAAA') || checkdnsrr($host, 'CNAME');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Returns 'active' | 'expired' | 'none'. */
    private function checkSsl(string $host): string
    {
        if ($host === '' || !function_exists('openssl_x509_parse')) {
            return 'none';
        }
        try {
            $ctx = stream_context_create(['ssl' => [
                'capture_peer_cert' => true, 'verify_peer' => false, 'verify_peer_name' => false,
            ]]);
            $client = @stream_socket_client(
                "ssl://{$host}:443", $errno, $errstr, 4,
                STREAM_CLIENT_CONNECT, $ctx
            );
            if (!$client) {
                return 'none';
            }
            $params = stream_context_get_params($client);
            fclose($client);
            $cert = $params['options']['ssl']['peer_certificate'] ?? null;
            if (!$cert) {
                return 'none';
            }
            $info = openssl_x509_parse($cert);
            $validTo = $info['validTo_time_t'] ?? 0;

            return ($validTo && $validTo > time()) ? 'active' : 'expired';
        } catch (\Throwable $e) {
            return 'none';
        }
    }

    private function host(string $value): string
    {
        $h = preg_replace('#^https?://#', '', trim(strtolower($value)));

        return explode('/', $h)[0];
    }

    private function apiKey(): string
    {
        return implode('-', array_map(
            fn () => str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
            range(1, 5)
        ));
    }

    private function org()
    {
        return DB::connection('organizations');
    }

    private function findSite($id)
    {
        $site = $this->org()->table('sites')->where('id', $id)->first();
        abort_if(!$site, 404);

        return $site;
    }
}
