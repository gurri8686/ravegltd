<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_products', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->default(0)->after('id'); // adjust 'after' as needed
			$table->unsignedBigInteger('supplier_id')->default(0)->after('id'); // adjust 'after' as needed
			$table->integer('is_archived')->default(0)->after('id'); // adjust 'after' as needed
			$table->text('remarks')->nullable(); // adjust 'after' as needed
        });
    }

    public function down(): void
    {
        Schema::table('stock_products', function (Blueprint $table) {
            $table->dropColumn('customer_id');
			$table->dropColumn('supplier_id');
			$table->dropColumn('is_archived');
			$table->dropColumn('remarks');
        });
    }
};