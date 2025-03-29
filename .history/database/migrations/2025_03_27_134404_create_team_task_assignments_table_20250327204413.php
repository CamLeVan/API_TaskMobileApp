<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeamTaskAssignmentsTable extends Migration
{
    public function up()
    {
        Schema::create('team_task_assignments', function (Blueprint $table) {
            $table->id('assignment_id');
            $table->unsignedBigInteger('team_task_id');
            $table->unsignedBigInteger('assigned_to');
            $table->enum('status', ['pending','in_progress','completed'])->default('pending');
            $table->integer('progress')->default(0);
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->nullable();

            $table->foreign('team_task_id')->references('team_task_id')->on('team_tasks')
                  ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('assigned_to')->references('user_id')->on('users')
                  ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('team_task_assignments');
    }
}
