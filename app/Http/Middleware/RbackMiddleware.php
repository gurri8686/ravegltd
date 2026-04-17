<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RbackMiddleware
{
	/**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        $user = \Auth::user();
        $currentRoute = $request->route()->getName();
        $roles = $user->roles->pluck('name');
        $check = function ($type) use ($roles) {
            foreach ($roles as $role) {
                if (strpos($role, $type) === 0) {
                    return true;
                }
            }
            return false;
        };
		
		if(in_array($currentRoute, config('nonRestrictedRoutes'))){
			return $next($request);
		}
		
        if ($user->can($currentRoute)) {
            return $next($request);
        }
          // return $next($request);
        return redirect('noPermission');
    }

}
