<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplierCreditUsagesTable extends Migration
{
    public function up()
    {
        Schema::create('supplier_credit_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('supplier_payment_id')->nullable()->comment('Links to supplier_payments record');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('supplier_credit_usages');
    }
}
