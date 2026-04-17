<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_invoice_products', function (Blueprint $table) {
            $table->integer('supplier_id')->default(0)->after('customer_invoice_id');
            // nullable() if supplier_id is optional
        });
    }

    public function down(): void
    {
        Schema::table('customer_invoice_products', function (Blueprint $table) {
            $table->dropColumn('supplier_id');
        });
    }
};

