<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Control-plane `sites` table — the host -> tenant-database map read by
 * App\Http\Middleware\DomainsMiddleware (the multidomain router). It lives in
 * the organizations DB (ts_organizations) and pre-dates this repo, so a
 * from-scratch `migrate` previously had NO `sites` table and every page 404'd.
 *
 * This creates the BASE table; the later 2026_05_26 migration
 * (add_verification_to_sites_table) appends the verification columns
 * (api_key, api_secret, status, ssl_status, dns_verified, verified_at) on top.
 *
 * hasTable-guarded so it's a no-op where the table already exists. NOT dropped
 * on rollback because it is shared control-plane data.
 */
return new class extends Migration
{
    private string $conn = 'organizations';

    public function up(): void
    {
        $schema = Schema::connection($this->conn);
        if ($schema->hasTable('sites')) {
            return;
        }

        $schema->create('sites', function (Blueprint $table) {
            $table->increments('id');
            $table->string('database', 200);          // tenant database name for this host
            $table->string('subdomain', 200)->unique();
            $table->string('domain', 200)->unique();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        // Shared control-plane table — intentionally NOT dropped on rollback.
    }
};
