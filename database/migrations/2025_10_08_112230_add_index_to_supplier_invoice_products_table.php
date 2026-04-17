<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_invoice_products', function (Blueprint $table) {
            $table->index(
                ['supplier_invoice_id', 'supplier_id', 'product_id'],
                'idx_supplier_invoice_supplier_product'
            );
        });
    }

    public function down(): void
    {
        Schema::table('supplier_invoice_products', function (Blueprint $table) {
            $table->dropIndex('idx_supplier_invoice_supplier_product');
        });
    }
};
