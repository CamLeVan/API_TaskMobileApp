<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupChatMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('group_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('sender_id');
            $table->text('message')->nullable();
            $table->string('file_url')->nullable();
            $table->timestamp('timestamp')->useCurrent();

            $table->foreign('team_id')->references('id')->on('teams')
                  ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('sender_id')->references('id')->on('users')
                  ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('group_chat_messages');
    }
}
