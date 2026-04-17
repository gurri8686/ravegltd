<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE stock_products MODIFY COLUMN event ENUM(
            'stock_added',
            'stock_updated',
            'stock_deleted',
            'customer_return',
            'supplier_return',
            'dump',
            'stock_consumed',
            'customer_stock_deleted'
        ) NOT NULL");
    }

    public function down(): void
    {
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
};
