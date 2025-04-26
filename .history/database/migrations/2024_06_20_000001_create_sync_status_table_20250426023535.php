<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSyncStatusTable extends Migration
{
    public function up()
    {
        Schema::create('sync_status', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('device_id');
            $table->timestamp('last_synced_at');
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')
                  ->onDelete('cascade');
            $table->unique(['user_id', 'device_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sync_status');
    }
} 