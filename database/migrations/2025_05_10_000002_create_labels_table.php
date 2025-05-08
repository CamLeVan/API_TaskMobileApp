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
        // Bảng labels đã tồn tại, chỉ tạo bảng task_labels nếu chưa tồn tại
        if (!Schema::hasTable('task_labels')) {
            Schema::create('task_labels', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('team_tasks')->onDelete('cascade');
                $table->foreignId('label_id')->constrained('labels')->onDelete('cascade');
                $table->timestamps();

                $table->unique(['task_id', 'label_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_labels');
    }
};
