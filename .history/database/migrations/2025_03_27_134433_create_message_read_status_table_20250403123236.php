<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessageReadStatusTable extends Migration
{
    public function up()
    {
        Schema::create('message_read_status', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('read_at')->useCurrent();

            $table->foreign('message_id')->references('id')->on('group_chat_messages')
                  ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')
                  ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('message_read_status');
    }
}
