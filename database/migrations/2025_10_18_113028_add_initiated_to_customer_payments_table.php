<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            // Add 'initiated' column only if it doesn't exist
            if (!Schema::hasColumn('customer_payments', 'initiated')) {
                $table->smallInteger('initiated')->default(0)->after('mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            if (Schema::hasColumn('customer_payments', 'initiated')) {
                $table->dropColumn('initiated');
            }
        });
    }
};
