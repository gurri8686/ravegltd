<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_invoice_products', function (Blueprint $table) {
            $table->unique([
                'supplier_invoice_id',
                'customer_invoice_id',
                'supplier_id',
                'product_id'
            ], 'unique_customer_invoice_product'); // optional index name
        });
    }

    public function down(): void
    {
        Schema::table('customer_invoice_products', function (Blueprint $table) {
            $table->dropUnique('unique_customer_invoice_product');
        });
    }
};
