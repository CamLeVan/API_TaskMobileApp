<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $table = 'notification_settings';
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'task_assignments',
        'task_reminders',
        'task_status_changes',
        'team_invitations',
        'team_updates',
        'chat_messages',
        'chat_mentions',
        'system_updates',
        'quiet_hours_start',
        'quiet_hours_end',
        'quiet_hours_enabled'
    ];

    protected $casts = [
        'task_assignments' => 'boolean',
        'task_reminders' => 'boolean',
        'task_status_changes' => 'boolean',
        'team_invitations' => 'boolean',
        'team_updates' => 'boolean',
        'chat_messages' => 'boolean',
        'chat_mentions' => 'boolean',
        'system_updates' => 'boolean',
        'quiet_hours_enabled' => 'boolean'
    ];

    /**
     * Get the user that owns the notification settings.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
