<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Control-plane `plans` table — per-site subscription PERIODS (start / expire),
 * in the organizations DB (ts_organizations). Distinct from `plan_tiers` (plan
 * definitions). This table pre-dates the repo and was never created by a
 * migration here, so a from-scratch `migrate` had no `plans` table.
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
        if ($schema->hasTable('plans')) {
            return;
        }

        $schema->create('plans', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('site_id');
            $table->dateTime('start');
            $table->dateTime('expire');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        // Shared control-plane table — intentionally NOT dropped on rollback.
    }
};
