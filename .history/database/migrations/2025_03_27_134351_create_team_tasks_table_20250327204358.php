<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeamTasksTable extends Migration
{
    public function up()
    {
        Schema::create('team_tasks', function (Blueprint $table) {
            $table->id('team_task_id');
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('created_by');
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('deadline')->nullable();
            $table->integer('priority')->nullable();
            $table->enum('status', ['pending','in_progress','completed','overdue'])->default('pending');
            $table->timestamps();

            $table->foreign('team_id')->references('team_id')->on('teams')
                  ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('created_by')->references('user_id')->on('users')
                  ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('team_tasks');
    }
}
