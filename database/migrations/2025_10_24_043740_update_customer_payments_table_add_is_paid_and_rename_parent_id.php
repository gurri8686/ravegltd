<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            // ✅ Rename parent_id → customer_payment_id (if exists)
            if (Schema::hasColumn('customer_payments', 'parent_id')) {
                $table->renameColumn('parent_id', 'customer_payment_id');
            }

            // ✅ Add is_paid column (if not already added)
            if (!Schema::hasColumn('customer_payments', 'is_paid')) {
                $table->smallInteger('is_paid')->default(0)->after('is_credited');
            }

            // ✅ Add is_runtime_payment column (if not already added)
            if (!Schema::hasColumn('customer_payments', 'is_runtime_payment')) {
                $table->smallInteger('is_runtime_payment')->default(0)->after('is_paid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            // Rollback rename
            if (Schema::hasColumn('customer_payments', 'customer_payment_id')) {
                $table->renameColumn('customer_payment_id', 'parent_id');
            }

            // Rollback added columns
            if (Schema::hasColumn('customer_payments', 'is_paid')) {
                $table->dropColumn('is_paid');
            }

            if (Schema::hasColumn('customer_payments', 'is_runtime_payment')) {
                $table->dropColumn('is_runtime_payment');
            }
        });
    }
};
