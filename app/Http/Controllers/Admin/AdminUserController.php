<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Platform admin accounts (the superadmin team) + their role-based access to
 * panel sections. Roles: superadmin (full) / finance / operations / technical.
 * The permissions matrix (role × section) is stored in admin_role_access
 * (organizations DB) and enforced by SuperadminMiddleware via self::allows().
 */
class AdminUserController extends Controller
{
    /** Platform admin roles (NOT vendor roles). superadmin = full access. */
    public const ADMIN_ROLES = ['superadmin', 'finance', 'operations', 'technical'];

    /** Panel sections that can be gated per role. */
    public const SECTIONS = [
        'dashboard'     => 'Dashboard',
        'vendors'       => 'Vendors',
        'domains'       => 'Domains',
        'plans'         => 'Plans',
        'subscriptions' => 'Subscriptions',
        'billing'       => 'Billing',
        'analytics'     => 'Analytics',
        'notifications' => 'Notifications',
        'audit'         => 'Audit Logs',
        'admin_users'   => 'Admin Users',
        'settings'      => 'Settings',
    ];

    public function index()
    {
        $admins = User::whereIn('role', self::ADMIN_ROLES)->orderBy('role')->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email', 'role', 'is_active', 'last_login_at']);

        // matrix: role => [section => allowed]
        $rows = DB::connection('organizations')->table('admin_role_access')->get();
        $matrix = [];
        foreach (['finance', 'operations', 'technical'] as $role) {
            foreach (array_keys(self::SECTIONS) as $section) {
                $matrix[$role][$section] = (bool) optional($rows->first(fn ($r) => $r->role === $role && $r->section === $section))->allowed;
            }
        }

        return view('admin.adminusers.index', [
            'admins'   => $admins,
            'roles'    => self::ADMIN_ROLES,
            'sections' => self::SECTIONS,
            'matrix'   => $matrix,
        ]);
    }

    public function create()
    {
        return view('admin.adminusers.create', ['roles' => self::ADMIN_ROLES]);
    }

    public function store(Request $request)
    {
        $data = $this->validateAdmin($request);

        $user = new User();
        $user->first_name = $data['first_name'];
        $user->last_name  = $data['last_name'] ?? '';
        $user->email      = $data['email'];
        $user->username   = $data['email'];
        $user->role       = $data['role'];
        $user->password   = Hash::make($data['password']);
        $user->is_active  = $request->boolean('is_active') ? 1 : 0;
        $user->save();

        return redirect()->route('admin.adminusers.index')->with('status', 'Admin user created.');
    }

    public function edit($id)
    {
        $admin = $this->findAdmin($id);

        return view('admin.adminusers.edit', ['admin' => $admin, 'roles' => self::ADMIN_ROLES]);
    }

    public function update(Request $request, $id)
    {
        $admin = $this->findAdmin($id);
        $data  = $this->validateAdmin($request, $admin);

        $admin->first_name = $data['first_name'];
        $admin->last_name  = $data['last_name'] ?? '';
        $admin->email      = $data['email'];
        $admin->role       = $data['role'];
        $admin->is_active  = $request->boolean('is_active') ? 1 : 0;
        if (!empty($data['password'])) {
            $admin->password = Hash::make($data['password']);
        }
        $admin->save();

        return redirect()->route('admin.adminusers.index')->with('status', 'Admin user updated.');
    }

    public function destroy($id)
    {
        $admin = $this->findAdmin($id);

        if ($admin->id === Auth::id()) {
            return back()->withErrors(['email' => "You can't delete your own account."]);
        }
        if ($admin->role === 'superadmin' && User::where('role', 'superadmin')->count() <= 1) {
            return back()->withErrors(['email' => "Can't delete the last superadmin."]);
        }

        $admin->delete();

        return redirect()->route('admin.adminusers.index')->with('status', 'Admin user deleted.');
    }

    /** Save the role × section permissions matrix. */
    public function updateMatrix(Request $request)
    {
        $access = $request->input('access', []); // access[role][section] = "1"
        $conn = DB::connection('organizations');

        foreach (['finance', 'operations', 'technical'] as $role) {
            foreach (array_keys(self::SECTIONS) as $section) {
                $allowed = isset($access[$role][$section]) ? 1 : 0;
                $conn->table('admin_role_access')->updateOrInsert(
                    ['role' => $role, 'section' => $section],
                    ['allowed' => $allowed, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }

        return back()->with('status', 'Permissions matrix updated.');
    }

    // ── used by SuperadminMiddleware ─────────────────────────────────────

    /** May this admin role access this panel section? Superadmin = always. */
    public static function allows(?string $role, ?string $section): bool
    {
        if ($role === 'superadmin') {
            return true;
        }
        if (!$role || !$section || !in_array($role, self::ADMIN_ROLES, true)) {
            return false;
        }
        // dashboard is the landing page — always reachable, never gated
        if ($section === 'dashboard') {
            return true;
        }

        return (bool) DB::connection('organizations')->table('admin_role_access')
            ->where('role', $role)->where('section', $section)->value('allowed');
    }

    /** Map a panel route name to its section (null = always allowed). */
    public static function sectionForRoute(?string $routeName): ?string
    {
        if (!$routeName) {
            return null;
        }
        $map = [
            'admin.dashboard'      => 'dashboard',
            'admin.vendors.'       => 'vendors',
            'admin.domains.'       => 'domains',
            'admin.plans.'         => 'plans',
            'admin.subscriptions.' => 'subscriptions',
            'admin.billing.'       => 'billing',
            'admin.analytics.'     => 'analytics',
            'admin.notifications.' => 'notifications',
            'admin.audit.'         => 'audit',
            'admin.adminusers.'    => 'admin_users',
            'admin.settings.'      => 'settings',
        ];
        foreach ($map as $prefix => $section) {
            if ($routeName === rtrim($prefix, '.') || str_starts_with($routeName, $prefix)) {
                return $section;
            }
        }

        return null; // account, logout, etc. — always allowed
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function validateAdmin(Request $request, ?User $admin = null): array
    {
        return $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'email'      => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore(optional($admin)->id)],
            'role'       => ['required', Rule::in(self::ADMIN_ROLES)],
            'password'   => ($admin ? 'nullable' : 'required') . '|string|min:6',
            'is_active'  => 'nullable|boolean',
        ]);
    }

    private function findAdmin($id): User
    {
        return User::whereIn('role', self::ADMIN_ROLES)->findOrFail($id);
    }
}
