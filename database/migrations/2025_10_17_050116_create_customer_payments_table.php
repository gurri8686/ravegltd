<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_invoice_id');
            $table->unsignedBigInteger('parent_id')->default(0);
            $table->decimal('debit', 8, 2)->default(0);
            $table->decimal('credit', 8, 2)->default(0);
            $table->smallInteger('is_refunded')->default(0);
            $table->unsignedBigInteger('payment_id');
            $table->text('note')->nullable();
            $table->enum('mode', ['full', 'partial']);
            $table->json('invoice_products')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Optional foreign keys:
            $table->foreign('customer_invoice_id')->references('id')->on('customer_invoices')->onDelete('cascade');
            //$table->foreign('payment_id')->references('id')->on('payments')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
    }
};
