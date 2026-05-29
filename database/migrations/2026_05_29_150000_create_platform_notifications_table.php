<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform announcements composed by the superadmin team. Each row targets an
 * audience (all / active / inactive vendors) and carries a level for styling.
 * Control-plane (organizations) DB — these are platform-wide, not tenant data.
 */
return new class extends Migration
{
    private string $conn = 'organizations';

    public function up(): void
    {
        $schema = Schema::connection($this->conn);
        if ($schema->hasTable('platform_notifications')) {
            return;
        }

        $schema->create('platform_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->string('audience')->default('all');   // all | active | inactive
            $table->string('level')->default('info');      // info | success | warning | critical
            $table->unsignedInteger('recipients')->default(0); // audience size at send time (real reach)
            $table->boolean('is_published')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->conn)->dropIfExists('platform_notifications');
    }
};
