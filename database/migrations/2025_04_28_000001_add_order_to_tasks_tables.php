<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('personal_tasks', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('status');
        });

        Schema::table('team_tasks', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('status');
        });
    }

    public function down()
    {
        Schema::table('personal_tasks', function (Blueprint $table) {
            $table->dropColumn('order');
        });

        Schema::table('team_tasks', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
