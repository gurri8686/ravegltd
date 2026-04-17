<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the enum to include 'stock_consumed'
        DB::statement("ALTER TABLE stock_products MODIFY COLUMN event ENUM(
            'stock_added',
            'stock_updated',
            'stock_deleted',
            'customer_return',
            'supplier_return',
            'dump',
            'stock_consumed'
        ) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to the old enum values (without stock_consumed)
        DB::statement("ALTER TABLE stock_products MODIFY COLUMN event ENUM(
            'stock_added',
            'stock_updated',
            'stock_deleted',
            'customer_return',
            'supplier_return',
            'dump'
        ) NOT NULL");
    }
};
