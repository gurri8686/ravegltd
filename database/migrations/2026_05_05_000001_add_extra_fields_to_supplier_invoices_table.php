<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExtraFieldsToSupplierInvoicesTable extends Migration
{
    public function up()
    {
        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->string('delivery_no')->nullable()->after('other_invoice_id');
            $table->string('supplier_invoice_no')->nullable()->after('delivery_no');
            $table->string('awb')->nullable()->after('supplier_invoice_no');
            $table->text('remarks')->nullable()->after('awb');
        });
    }

    public function down()
    {
        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->dropColumn(['delivery_no', 'supplier_invoice_no', 'awb', 'remarks']);
        });
    }
}
