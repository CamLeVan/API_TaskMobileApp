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
        Schema::table('team_tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('column_id')->nullable()->after('status');
            $table->foreign('column_id')->references('id')->on('kanban_columns')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_tasks', function (Blueprint $table) {
            $table->dropForeign(['column_id']);
            $table->dropColumn('column_id');
        });
    }
};
