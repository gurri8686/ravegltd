<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CustomerInvoiceProductss extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_invoice_products', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_invoice_id');
            $table->integer('customer_id');
            $table->integer('product_id');
            $table->text('product_details')->comment('json data');
            $table->integer('quantity');
            $table->decimal('unit_price',10,2);
            $table->decimal('sub_total',10,2);
            $table->smallInteger('is_archive')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_invoice_products');
    }
}
