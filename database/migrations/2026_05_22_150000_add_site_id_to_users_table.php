<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a vendor (user) to the site/domain it belongs to. site_id references
 * ts_organizations.sites.id (a different DB, so no FK — just the id).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'site_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('site_id')->nullable()->index()->after('role');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'site_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('site_id');
            });
        }
    }
};
