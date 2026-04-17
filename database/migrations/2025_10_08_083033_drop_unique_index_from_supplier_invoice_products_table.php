<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_invoice_products', function (Blueprint $table) {
            // Replace 'your_index_name' with the actual index name
            $table->dropUnique('unique_supplier_invoice_product');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_invoice_products', function (Blueprint $table) {
            $table->unique(['supplier_invoice_id', 'supplier_id', 'product_id'], 'unique_supplier_invoice_product');
        });
    }
};
