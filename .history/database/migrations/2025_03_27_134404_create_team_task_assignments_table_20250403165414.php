<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('team_task_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_task_id');
            $table->unsignedBigInteger('assigned_to');
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->integer('progress')->default(0);
            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->foreign('team_task_id')->references('id')->on('team_tasks')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('team_task_assignments');
    }
};
