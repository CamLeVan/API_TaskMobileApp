<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            // Sử dụng tên cột "user_id" nếu muốn, hoặc giữ "id" theo chuẩn Laravel
            $table->id('user_id');
            $table->integer("phone");
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
