<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerCreditUsagesTable extends Migration
{
    public function up()
    {
        Schema::create('customer_credit_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('customer_payment_id')->nullable()->comment('Links to parent customer_payments record');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_credit_usages');
    }
}
