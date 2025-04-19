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
        Schema::table('group_chat_messages', function (Blueprint $table) {
            $table->string('status')->default('sent');
            $table->string('client_temp_id')->nullable()->index();
            $table->dropColumn('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_chat_messages', function (Blueprint $table) {
            $table->dropColumn(['status', 'client_temp_id']);
            $table->timestamp('timestamp');
        });
    }
}; 