<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('personal_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('personal_tasks', 'reminder_minutes_before')) {
                $table->integer('reminder_minutes_before')->nullable()->after('labels');
            }
        });
    }

    public function down()
    {
        Schema::table('personal_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('personal_tasks', 'reminder_minutes_before')) {
                $table->dropColumn('reminder_minutes_before');
            }
        });
    }
};
