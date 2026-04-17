<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplierInvoicePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('supplier_payments', function (Blueprint $table) {
        $table->id();

        $table->smallInteger('is_archived')->default(0);
        $table->unsignedBigInteger('supplier_id')->nullable();
        $table->unsignedBigInteger('supplier_invoice_id');
        $table->unsignedBigInteger('supplier_payment_id')->default(0);

        $table->decimal('debt', 8, 2)->default(0.00);
        $table->decimal('credit', 8, 2)->default(0.00);
        $table->decimal('amount', 8, 2)->default(0.00);

        $table->integer('is_discounted')->default(0);
        $table->smallInteger('is_refunded')->default(0);
        $table->smallInteger('is_credited')->default(0);
        $table->smallInteger('is_paid')->default(0);
        $table->smallInteger('is_runtime_payment')->default(0);

        $table->unsignedBigInteger('payment_id');
        $table->text('note')->nullable();

        $table->enum('mode', ['none','full', 'partial'])->default('full');

        $table->smallInteger('initiated')->default(0);
        $table->json('invoice_products')->nullable();

        $table->timestamp('created_at')->useCurrent();
        $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

        // Optional: foreign keys (uncomment and adjust if needed)
        // $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
        // $table->foreign('supplier_invoice_id')->references('id')->on('supplier_invoices')->onDelete('cascade');
        // $table->foreign('supplier_payment_id')->references('id')->on('supplier_payments')->onDelete('cascade');
        // $table->foreign('payment_id')->references('id')->on('invoice_payments')->onDelete('cascade');
    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('supplier_payments');
    }
}
