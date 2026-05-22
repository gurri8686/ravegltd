<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerHistoryEmailsTable extends Migration
{
    public function up()
    {
        Schema::create('customer_history_emails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('to_email');
            $table->string('cc_email')->nullable();
            $table->string('subject');
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();
            $table->integer('invoice_count')->default(0);
            $table->string('status')->default('sent');
            $table->text('error')->nullable();
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_history_emails');
    }
}
