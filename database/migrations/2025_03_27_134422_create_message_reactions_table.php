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

            // Chỉ khai báo 1 lần message_id và tạo foreign key ngay
            $table->foreignId('message_id')->constrained('group_chat_messages')->onDelete('cascade');

            // Khai báo user_id và foreign key
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->string('reaction', 10);
            $table->timestamps();

            // Unique constraint để không trùng reaction từ 1 user cho 1 message
            $table->unique(['message_id', 'user_id', 'reaction']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('message_reactions');
    }
}
