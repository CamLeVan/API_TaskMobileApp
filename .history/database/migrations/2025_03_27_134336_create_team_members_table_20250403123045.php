<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeamMembersTable extends Migration
{
    public function up()
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id(); // Cột auto-increment "id"
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('role', ['manager','member'])->default('member');
            $table->timestamp('joined_at')->useCurrent();
            
            $table->foreign('team_id')->references('id')->on('teams')
                  ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')
                  ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('team_members');
    }
}
