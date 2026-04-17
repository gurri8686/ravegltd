<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Use raw SQL to modify the timestamps
        DB::statement("
            ALTER TABLE invoice_payments
            MODIFY created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            MODIFY updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ");
    }

    public function down(): void
    {
        // Revert back to nullable timestamps (default Laravel behavior)
        DB::statement("
            ALTER TABLE invoice_payments
            MODIFY created_at TIMESTAMP NULL DEFAULT NULL,
            MODIFY updated_at TIMESTAMP NULL DEFAULT NULL
        ");
    }
};
