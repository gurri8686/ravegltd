<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeneralSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
	{
		Schema::create('general_settings', function (Blueprint $table) {
			$table->id();
			$table->string('setting')->nullable();
			$table->string('title')->nullable();
			$table->unsignedBigInteger('user_id')->nullable();
			$table->boolean('status')->default(1);
			$table->boolean('is_archive')->default(0);
			$table->timestamps();

			// Optional: if user_id references users table
			// $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
		});
	}


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('general_settings');
    }
}
