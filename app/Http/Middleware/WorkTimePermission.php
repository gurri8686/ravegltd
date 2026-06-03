<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\WorkTimePermission as WorkTimePermissionModel;

class WorkTimePermission
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
        $roleName = trim($roles, '[""]');
		
		if(in_array($currentRoute, config('nonRestrictedRoutes'))){
			return $next($request);
		}
        // Admins and the platform superadmin are exempt from work-time slots.
        if ($user->hasRole(['admin','Admin','superadmin','Superadmin']) || $user->role === 'superadmin') {
            return $next($request);
        } else {
            $roleid = $user->roles->pluck('id');
            $roleid = trim($roleid, '[""]');
            $roleDetails = WorkTimePermissionModel::where('role_id', $roleid)->get()->first();
            if ($roleDetails) {
                $roleDetails = $roleDetails->toarray();
                if ($roleDetails['is_24_hours'] == 1) {
                    return $next($request);
                } else {
                    $startTime = $roleDetails['start_time'];
                    $endTime = $roleDetails['end_time'];
                    $currentTime = date('H:i', time());

                    if ((strtotime($currentTime) >= strtotime($startTime)) && (strtotime($currentTime) <= strtotime($endTime))) {
                        return $next($request);
                    } else {
                        return redirect('workTime');
                    }
                }

            }
            return redirect('workTime');
        }
    }
}