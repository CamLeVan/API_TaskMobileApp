<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessageReactionsTable extends Migration
{
    public function up()
    {
        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->unsignedBigInteger('user_id');
            $table->string('reaction', 10);
            $table->timestamps();
            
            $table->foreign('message_id')->references('id')->on('group_chat_messages')
                  ->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')
                  ->onDelete('cascade');
                  
            $table->unique(['message_id', 'user_id', 'reaction']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('message_reactions');
    }
} 