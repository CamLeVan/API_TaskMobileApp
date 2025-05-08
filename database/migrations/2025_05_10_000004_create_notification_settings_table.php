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
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('task_assignments')->default(true);
            $table->boolean('task_reminders')->default(true);
            $table->boolean('task_status_changes')->default(true);
            $table->boolean('team_invitations')->default(true);
            $table->boolean('team_updates')->default(true);
            $table->boolean('chat_messages')->default(true);
            $table->boolean('chat_mentions')->default(true);
            $table->boolean('system_updates')->default(true);
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            $table->boolean('quiet_hours_enabled')->default(false);
            $table->timestamps();
            
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
