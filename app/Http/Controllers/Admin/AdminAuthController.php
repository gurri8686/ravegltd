<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Superadmin authentication — a self-contained login at /admin/login,
 * isolated from the normal app login. Only users holding the 'superadmin'
 * role may sign in here.
 */
class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        if (($u = Auth::user()) && in_array($u->role, \App\Http\Controllers\Admin\AdminUserController::ADMIN_ROLES, true)) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string',
            'password' => 'required|string',
        ]);

        // Accept email OR username, same as the main login.
        $user = User::where('email', $request->email)
            ->orWhere('username', $request->email)
            ->first();

        if (!$user || !in_array($user->role, \App\Http\Controllers\Admin\AdminUserController::ADMIN_ROLES, true)) {
            return back()
                ->withErrors(['email' => 'These credentials do not match an admin account.'])
                ->withInput($request->only('email'));
        }

        $ok = Auth::attempt([
            'email'     => $user->email,
            'password'  => $request->password,
            'is_active' => 1,
        ], $request->boolean('remember'));

        if (!$ok) {
            return back()
                ->withErrors(['email' => 'Invalid credentials or inactive account.'])
                ->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
