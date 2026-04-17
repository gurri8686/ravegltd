<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->enum('type', ['supplier', 'customer']);
			$table->integer('stock')->default(0);
            $table->unsignedBigInteger('invoice_id')->default(0);
            $table->enum('event', [
                'stock_added',
                'stock_updated',
                'stock_deleted',
                'customer_return',
                'supplier_return',
                'dump'
            ]);
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();

            // Optional foreign key (if products table exists)
            // $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_products');
    }
};
