<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plan tiers (Basic / Professional / Enterprise …) — the subscription plan
 * DEFINITIONS the superadmin configures: price, limits, and feature toggles.
 * Lives in the control-plane (organizations) DB. Distinct from `plans`
 * (which records per-site subscription periods).
 */
return new class extends Migration
{
    private string $conn = 'organizations';

    public function up(): void
    {
        $schema = Schema::connection($this->conn);
        if ($schema->hasTable('plan_tiers')) {
            return;
        }

        $schema->create('plan_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('billing_cycle', 20)->default('monthly'); // monthly | yearly
            $table->string('currency', 8)->default('GBP');

            // limits — null means unlimited
            $table->integer('user_limit')->nullable();
            $table->integer('product_limit')->nullable();
            $table->integer('customer_limit')->nullable();
            $table->integer('supplier_limit')->nullable();
            $table->integer('storage_limit_mb')->nullable();

            // feature toggles
            $table->boolean('f_reports')->default(false);
            $table->boolean('f_multi_user')->default(false);
            $table->boolean('f_analytics')->default(false);
            $table->boolean('f_purchases')->default(true);
            $table->boolean('f_customers')->default(true);
            $table->boolean('f_suppliers')->default(true);

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->conn)->dropIfExists('plan_tiers');
    }
};
