<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Sets up the role-based superadmin/vendor structure.
 *
 *   - Ensures the 'superadmin' and 'vendor' roles exist (web guard).
 *   - Ensures a superadmin login exists (idempotent — matched by email).
 *
 * Run against the desired domain's DB, e.g.:
 *   php artisan db:seed --class=Database\\Seeders\\SuperadminSeeder --domain=ts.localhost
 */
class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['superadmin', 'vendor'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $email = 'superadmin@ravegltd.local';
        $user = User::where('email', $email)->first() ?: new User();

        $user->email      = $email;
        $user->username   = $email;
        $user->first_name = 'Super';
        $user->last_name  = 'Admin';
        $user->password   = Hash::make('ChangeMe@123');
        $user->is_active  = 1;
        $user->role       = 'superadmin';
        $user->save();

        $this->command->info("Superadmin ready: {$email} / ChangeMe@123 (change after first login)");
    }
}
