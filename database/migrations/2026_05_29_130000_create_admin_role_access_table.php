<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permissions matrix for platform admin roles (finance / operations /
 * technical) × panel sections. Superadmin always has full access and is not
 * stored here. Lives in the control-plane (organizations) DB.
 */
return new class extends Migration
{
    private string $conn = 'organizations';

    public function up(): void
    {
        $schema = Schema::connection($this->conn);
        if ($schema->hasTable('admin_role_access')) {
            return;
        }

        $schema->create('admin_role_access', function (Blueprint $table) {
            $table->id();
            $table->string('role', 40);
            $table->string('section', 60);
            $table->boolean('allowed')->default(false);
            $table->timestamps();
            $table->unique(['role', 'section']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->conn)->dropIfExists('admin_role_access');
    }
};
