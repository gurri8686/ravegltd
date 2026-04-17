<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->enum('invoice_type', ['normal', 'advance'])
                  ->default('normal')
                  ->after('supplier_id'); // replace "some_column" with the column after which you want to add
        });
    }

    public function down(): void
    {
        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->dropColumn('invoice_type');
        });
    }
};
