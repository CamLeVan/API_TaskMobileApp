<?php

namespace App\Notifications;

use App\Models\PersonalTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskReminderNotification extends Notification
{
    use Queueable;

    protected $task;

    public function __construct(PersonalTask $task)
    {
        $this->task = $task;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Nhắc nhở: ' . $this->task->title)
            ->line('Đây là lời nhắc cho công việc sắp đến hạn:')
            ->line('Tiêu đề: ' . $this->task->title)
            ->line('Mô tả: ' . $this->task->description)
            ->line('Hạn chót: ' . $this->task->deadline->format('d/m/Y H:i'))
            ->action('Xem chi tiết', url('/tasks/' . $this->task->id));
    }

    public function toArray($notifiable)
    {
        return [
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'deadline' => $this->task->deadline,
            'message' => 'Công việc sắp đến hạn: ' . $this->task->title
        ];
    }
}