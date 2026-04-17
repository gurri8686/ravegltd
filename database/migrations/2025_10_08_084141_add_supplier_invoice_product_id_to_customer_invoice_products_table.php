<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_invoice_products', function (Blueprint $table) {
            $table->integer('supplier_invoice_product_id')->default(0)->after('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('customer_invoice_products', function (Blueprint $table) {
            $table->dropColumn('supplier_invoice_product_id');
        });
    }
};
