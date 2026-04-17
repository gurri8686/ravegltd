<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            // Add customer_id if it doesn't exist
            if (!Schema::hasColumn('customer_payments', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            if (Schema::hasColumn('customer_payments', 'customer_id')) {
                $table->dropColumn('customer_id');
            }
        });
    }
};
