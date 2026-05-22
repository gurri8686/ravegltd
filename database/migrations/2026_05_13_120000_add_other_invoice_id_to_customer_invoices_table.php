<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `other_invoice_id` to customer_invoices so the original invoice number
 * from imported Excel files (e.g. "153385") can be preserved alongside the
 * auto-incremented internal id. Mirrors the column already present on
 * supplier_invoices (varchar 60, nullable).
 */
return new class extends Migration {
    public function up()
    {
        if (!Schema::hasColumn('customer_invoices', 'other_invoice_id')) {
            Schema::table('customer_invoices', function (Blueprint $table) {
                $table->string('other_invoice_id', 60)->nullable()->after('id');
                // Index speeds up duplicate-detection lookups on (other_invoice_id, customer_id)
                $table->index(['other_invoice_id', 'customer_id'], 'idx_cinv_other_inv_customer');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('customer_invoices', 'other_invoice_id')) {
            Schema::table('customer_invoices', function (Blueprint $table) {
                $table->dropIndex('idx_cinv_other_inv_customer');
                $table->dropColumn('other_invoice_id');
            });
        }
    }
};
