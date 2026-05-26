<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds SSL / DNS verification tracking to the control-plane `sites` table.
 *
 * NOTE: `sites` lives in the organizations (control) database, not the default
 * tenant DB — hence Schema::connection('organizations'). The table also carries
 * a legacy 0000-00-00 datetime default that trips strict mode on ALTER, so we
 * relax sql_mode for the session first. Idempotent (guards each column).
 */
return new class extends Migration
{
    private string $conn = 'organizations';

    public function up(): void
    {
        $schema = Schema::connection($this->conn);
        if (!$schema->hasTable('sites')) {
            return;
        }

        // legacy 0000-00-00 datetime default on this table breaks strict-mode ALTER
        DB::connection($this->conn)->statement("SET SESSION sql_mode=''");

        $schema->table('sites', function (Blueprint $table) use ($schema) {
            if (!$schema->hasColumn('sites', 'ssl_status')) {
                $table->string('ssl_status', 20)->nullable()->after('status');
            }
            if (!$schema->hasColumn('sites', 'dns_verified')) {
                $table->boolean('dns_verified')->default(false)->after('ssl_status');
            }
            if (!$schema->hasColumn('sites', 'verified_at')) {
                $table->dateTime('verified_at')->nullable()->after('dns_verified');
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->conn);
        if (!$schema->hasTable('sites')) {
            return;
        }

        $schema->table('sites', function (Blueprint $table) use ($schema) {
            foreach (['ssl_status', 'dns_verified', 'verified_at'] as $col) {
                if ($schema->hasColumn('sites', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
