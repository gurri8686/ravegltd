<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Key/value store for platform-wide superadmin settings (branding, SMTP,
 * payment keys, security, appearance). Control-plane (organizations) DB.
 */
return new class extends Migration
{
    private string $conn = 'organizations';

    public function up(): void
    {
        $schema = Schema::connection($this->conn);
        if ($schema->hasTable('platform_settings')) {
            return;
        }

        $schema->create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->conn)->dropIfExists('platform_settings');
    }
};
