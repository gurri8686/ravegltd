<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            // Drop the foreign key (but keep the column)
            $table->dropForeign(['customer_invoice_id']);

            // Add the new column
            $table->integer('is_discounted')->default(0)->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            // Recreate the foreign key
            $table->foreign('customer_invoice_id')
                  ->references('id')
                  ->on('customer_invoices')
                  ->onDelete('cascade');

            // Remove the added column
            $table->dropColumn('is_discounted');
        });
    }
};
