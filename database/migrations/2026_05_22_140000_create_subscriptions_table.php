<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vendor subscriptions. A vendor (user) can have many subscription periods;
 * the latest by `expire` is the current one. Powers the Subscription menu
 * (listing + history) and the subscription status shown on the vendor list.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('vendor_id')->index();
                $table->dateTime('start');
                $table->dateTime('expire');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
