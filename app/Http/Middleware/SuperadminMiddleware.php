<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Admin\AdminUserController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Guards the /admin/* panel. Access is role-based: the user must hold a
 * platform admin role (superadmin / finance / operations / technical).
 * Superadmin has full access; the others are gated per the permissions
 * matrix (admin_role_access) by section. Anyone else is logged out.
 */
class SuperadminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user || !in_array($user->role, AdminUserController::ADMIN_ROLES, true)) {
            Auth::logout();
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Admin panel access only.']);
        }

        // section-level access for non-superadmin admins
        if ($user->role !== 'superadmin') {
            $section = AdminUserController::sectionForRoute($request->route() ? $request->route()->getName() : null);
            if ($section && !AdminUserController::allows($user->role, $section)) {
                return redirect()->route('admin.dashboard')
                    ->withErrors(['email' => 'You do not have access to that section.']);
            }
        }

        return $next($request);
    }
}
