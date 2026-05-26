<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Guards the /admin/* superadmin panel. Access is purely role-based:
 * the authenticated user must hold the 'superadmin' role. Anyone else
 * (guests, vendors, admins, managers) is logged out and bounced to the
 * superadmin login.
 */
class SuperadminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'superadmin') {
            Auth::logout();
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Superadmin access only.']);
        }

        return $next($request);
    }
}
