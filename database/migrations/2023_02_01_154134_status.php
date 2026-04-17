<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Status extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_invoice_orders', function (Blueprint $table) {
            $table->integer('status')->comments('0=pending,1=completed,2=future,3=suspended');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer_invoice_orders', function (Blueprint $table) {
            $table->dropColumn(['status']);
        });
    }
}
