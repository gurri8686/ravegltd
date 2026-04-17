<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('original_name');          // User-uploaded name
            $table->string('extension', 10);
            $table->unsignedBigInteger('size');       // in bytes
            $table->enum('access', ['private','public'])->default('private');
            $table->boolean('is_archived')->default(false);
            $table->string('mime_type')->nullable();
            $table->binary('content');              // <-- file binary
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('files');
    }
}
