<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Platform settings (superadmin) — branding, SMTP, payment keys, security,
 * appearance. Stored as key/value in platform_settings (organizations DB).
 * Branding (platform name, accent colour, logo) is read live by the panel
 * layout; SMTP/payment keys are stored for when those integrations are wired.
 */
class SettingsController extends Controller
{
    /** Field schema grouped into tabs. type: text|email|password|number|select|color|file|toggle */
    public const TABS = [
        'general' => ['label' => 'General', 'icon' => 'bi-sliders', 'fields' => [
            'platform_name' => ['label' => 'Platform name', 'type' => 'text', 'placeholder' => 'R & A Veg'],
            'support_email' => ['label' => 'Support email', 'type' => 'email', 'placeholder' => 'support@example.com'],
            'logo'          => ['label' => 'Logo', 'type' => 'file'],
            'favicon'       => ['label' => 'Favicon', 'type' => 'file'],
        ]],
        'payments' => ['label' => 'Payments', 'icon' => 'bi-credit-card', 'fields' => [
            'stripe_key'       => ['label' => 'Stripe publishable key', 'type' => 'text'],
            'stripe_secret'    => ['label' => 'Stripe secret key', 'type' => 'password'],
            'razorpay_key'     => ['label' => 'Razorpay key id', 'type' => 'text'],
            'razorpay_secret'  => ['label' => 'Razorpay secret', 'type' => 'password'],
            'paypal_client_id' => ['label' => 'PayPal client id', 'type' => 'text'],
            'paypal_secret'    => ['label' => 'PayPal secret', 'type' => 'password'],
        ]],
        'security' => ['label' => 'Security', 'icon' => 'bi-shield-lock', 'fields' => [
            'password_min_length' => ['label' => 'Minimum password length', 'type' => 'number', 'placeholder' => '8'],
            'session_timeout'     => ['label' => 'Session timeout (minutes)', 'type' => 'number', 'placeholder' => '120'],
            'require_2fa'         => ['label' => 'Require two-factor auth', 'type' => 'toggle'],
        ]],
        'appearance' => ['label' => 'Appearance', 'icon' => 'bi-palette', 'fields' => [
            'default_theme' => ['label' => 'Default theme', 'type' => 'select', 'options' => ['light' => 'Light', 'dark' => 'Dark']],
            'accent_color'  => ['label' => 'Accent colour', 'type' => 'color'],
        ]],
    ];

    private static ?array $cache = null;

    public function index()
    {
        return view('admin.settings.index', ['tabs' => self::TABS, 'values' => self::all()]);
    }

    public function update(Request $request)
    {
        foreach (self::TABS as $tab) {
            foreach ($tab['fields'] as $key => $def) {
                if ($def['type'] === 'file') {
                    if ($request->hasFile($key)) {
                        $this->put($key, $this->storeFile($request->file($key), $key));
                    }
                    continue;
                }
                if ($def['type'] === 'toggle') {
                    $this->put($key, $request->boolean($key) ? '1' : '0');
                    continue;
                }
                // password/secret: blank means "keep existing" (don't wipe)
                if ($def['type'] === 'password') {
                    $val = (string) $request->input($key, '');
                    if ($val !== '') {
                        $this->put($key, $val);
                    }
                    continue;
                }
                $this->put($key, (string) $request->input($key, ''));
            }
        }

        self::$cache = null;

        return back()->with('status', 'Settings saved.');
    }

    // ── helpers / accessors ──────────────────────────────────────────────

    /** All settings as a key => value array (cached per request). */
    public static function all(): array
    {
        if (self::$cache === null) {
            try {
                self::$cache = DB::connection('organizations')->table('platform_settings')
                    ->pluck('value', 'key')->all();
            } catch (\Throwable $e) {
                // table not migrated yet (e.g. fresh deploy before `migrate`) —
                // fall back to defaults instead of crashing every admin page
                self::$cache = [];
            }
        }

        return self::$cache;
    }

    /** Read a single setting (used by the layout for live branding). */
    public static function get(string $key, $default = null)
    {
        $v = self::all()[$key] ?? null;

        return ($v === null || $v === '') ? $default : $v;
    }

    private function put(string $key, ?string $value): void
    {
        DB::connection('organizations')->table('platform_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    private function storeFile($file, string $key): string
    {
        $dir = public_path('uploads/branding');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $name = $key . '_' . time() . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $name);

        return 'uploads/branding/' . $name;
    }
}
