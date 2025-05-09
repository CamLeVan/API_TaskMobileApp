<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('thumbnail_path')->nullable();
            $table->string('file_type');
            $table->unsignedBigInteger('file_size');
            $table->unsignedBigInteger('folder_id')->nullable();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('uploaded_by');
            $table->enum('access_level', ['public', 'team', 'private', 'specific_users'])->default('team');
            $table->unsignedInteger('current_version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');
            // Folder foreign key will be added in a separate migration
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
