<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {


        // Chạy mỗi 5 phút để kiểm tra và gửi nhắc nhở
        $schedule->command('tasks:send-reminders')->everyFiveMinutes();
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
    }
}
