<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Không cần thêm trường order vì đã được thêm trong migration 2025_04_28_000001_add_order_to_tasks_tables
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không cần thực hiện gì vì không có thay đổi trong up()
    }
};
