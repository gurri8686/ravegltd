<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplierInvoiceOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('supplier_invoice_orders', function (Blueprint $table) {
            $table->id();
            $table->integer('supplier_invoice_id');
            $table->integer('supplier_id');
            $table->decimal('sub_total',10,2);
            $table->decimal('total',10,2);
            $table->decimal('vat',10,2);
            $table->decimal('porterage',10,2);
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
        Schema::dropIfExists('supplier_invoice_orders');
    }
}
