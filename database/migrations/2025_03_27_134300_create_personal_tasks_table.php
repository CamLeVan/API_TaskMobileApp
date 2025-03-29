<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePersonalTasksTable extends Migration
{
    public function up()
    {
        Schema::create('personal_tasks', function (Blueprint $table) {
            $table->id('task_id');
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('deadline')->nullable();
            $table->integer('priority')->nullable();
            $table->enum('status', ['pending','in_progress','completed','overdue'])->default('pending');
            $table->timestamps();
            
            $table->foreign('user_id')->references('user_id')->on('users')
                  ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('personal_tasks');
    }
}
