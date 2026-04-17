<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotesToCustomerInvoicesTable extends Migration
{
    public function up()
    {
        Schema::table('customer_invoices', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('customer_invoices', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
}
