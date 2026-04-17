<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            // Rename column safely
            if (Schema::hasColumn('customer_payments', 'debit') && !Schema::hasColumn('customer_payments', 'debt')) {
                $table->renameColumn('debit', 'debt');
            }

            // Add enum column only if it doesn't exist
            if (!Schema::hasColumn('customer_payments', 'mode')) {
                $table->enum('mode', ['partial', 'full', 'initiate'])->default('initiate')->after('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            if (Schema::hasColumn('customer_payments', 'debt') && !Schema::hasColumn('customer_payments', 'debit')) {
                $table->renameColumn('debt', 'debit');
            }

            if (Schema::hasColumn('customer_payments', 'mode')) {
                $table->dropColumn('mode');
            }
        });
    }
};
