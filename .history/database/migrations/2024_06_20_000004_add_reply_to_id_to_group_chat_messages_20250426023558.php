<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReplyToIdToGroupChatMessages extends Migration
{
    public function up()
    {
        Schema::table('group_chat_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('reply_to_id')->nullable()->after('sender_id');
            $table->foreign('reply_to_id')->references('id')->on('group_chat_messages')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('group_chat_messages', function (Blueprint $table) {
            $table->dropForeign(['reply_to_id']);
            $table->dropColumn('reply_to_id');
        });
    }
} 