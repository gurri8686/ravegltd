<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WorkTimePermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('work_time_permissions', function (Blueprint $table){
            $table->id();
            $table->string('role_id');
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->smallInteger('is_24_hours')->nullable();
            $table->smallInteger('view')->nullable();
            $table->smallInteger('create')->nullable();
            $table->smallInteger('edit')->nullable();
            $table->smallInteger('delete')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('work_time_permissions');
    }
}
