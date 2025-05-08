<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $table = 'calendar_events';
    protected $primaryKey = 'id';

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'type',
        'team_id',
        'user_id'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime'
    ];

    /**
     * Get the user that created the event.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the team associated with the event.
     */
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }

    /**
     * Get the participants for the event.
     */
    public function participants()
    {
        return $this->belongsToMany(User::class, 'calendar_event_participants', 'event_id', 'user_id')
                    ->withTimestamps();
    }
}
