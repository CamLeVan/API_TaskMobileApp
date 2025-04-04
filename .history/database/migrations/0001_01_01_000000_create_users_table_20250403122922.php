<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password'); // Bạn có thể đổi tên thành "password" nếu muốn
            $table->timestamps(); // Tạo created_at và updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}
