<?php
#https://spatie.be/docs/laravel-permission/v5/basic-usage/super-admin#breadcrumb
namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use App\Models\Site;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot(Request $request)
    {
        $this->registerPolicies();
        Gate::before(function ($user, $ability) {
            // Full-access roles bypass every permission check (spatie super-admin
            // pattern). 'superadmin' covers the platform superadmin, who holds the
            // spatie 'superadmin' role and also carries role column 'superadmin'.
            return ($user->hasRole(['admin','Admin','superadmin','Superadmin'])
                || $user->role === 'superadmin') ? true : null;
        });
    }
}
