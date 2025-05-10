<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'theme',
        'language',
        'timezone',
        'date_format',
        'time_format',
        'start_day_of_week',
        'enable_biometric',
        'auto_save_drafts',
        'default_view',
        'notification_settings',
        'calendar_sync'
    ];

    protected $casts = [
        'notification_settings' => 'array',
        'calendar_sync' => 'array',
        'enable_biometric' => 'boolean',
        'auto_save_drafts' => 'boolean',
        'start_day_of_week' => 'integer'
    ];

    /**
     * Get the user that owns the settings.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
