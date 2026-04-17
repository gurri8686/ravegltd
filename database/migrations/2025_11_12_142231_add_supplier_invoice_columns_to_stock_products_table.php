<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_products', function (Blueprint $table) {
            // Add columns (nullable for backward compatibility)
            $table->unsignedBigInteger('supplier_invoice_product_id')->default(0)->after('invoice_id');
            $table->unsignedBigInteger('supplier_invoice_id')->default(0)->after('supplier_invoice_product_id');
        });
    }

    public function down(): void
    {
        Schema::table('stock_products', function (Blueprint $table) {
            $table->dropColumn(['supplier_invoice_product_id', 'supplier_invoice_id']);
        });
    }
};
