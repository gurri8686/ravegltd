<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSupplierCreditUsagesTableSeeder extends Seeder
{
    public function run()
    {
        if (!Schema::hasTable('supplier_credit_usages')) {
            DB::statement("
                CREATE TABLE supplier_credit_usages (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    supplier_id BIGINT UNSIGNED NOT NULL,
                    supplier_payment_id BIGINT UNSIGNED NULL COMMENT 'Links to supplier_payments record',
                    amount DECIMAL(10,2) NOT NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ");
            $this->command->info('supplier_credit_usages table created.');
        } else {
            $this->command->info('supplier_credit_usages table already exists, skipping.');
        }
    }
}
