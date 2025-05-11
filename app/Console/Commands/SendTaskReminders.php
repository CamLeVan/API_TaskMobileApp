<?php

namespace App\Console\Commands;

use App\Models\PersonalTask;
use App\Notifications\TaskReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendTaskReminders extends Command
{
    protected $signature = 'tasks:send-reminders';
    protected $description = 'Send reminders for upcoming tasks';

    public function handle()
    {
        $now = Carbon::now();
        
        // Tìm các task có reminder_minutes_before và deadline
        $tasks = PersonalTask::whereNotNull('reminder_minutes_before')
            ->whereNotNull('deadline')
            ->where('status', '!=', 'completed')
            ->get();
            
        foreach ($tasks as $task) {
            // Tính thời điểm gửi nhắc nhở
            $reminderTime = (clone $task->deadline)->subMinutes($task->reminder_minutes_before);
            
            // Nếu thời điểm nhắc nhở nằm trong khoảng 5 phút tới
            if ($now->diffInMinutes($reminderTime, false) <= 5 && $now->diffInMinutes($reminderTime, false) >= 0) {
                // Gửi thông báo
                $task->user->notify(new TaskReminderNotification($task));
                $this->info("Sent reminder for task: {$task->title}");
            }
        }
        
        return 0;
    }
}