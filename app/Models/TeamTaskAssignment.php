<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamTaskAssignment extends Model
{
    use HasFactory;

    protected $table = 'team_task_assignments';
    protected $primaryKey = 'assignment_id';

    protected $fillable = [
        'team_task_id',
        'assigned_to',
        'status',
        'progress'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'updated_at' => 'datetime',
        'progress' => 'integer'
    ];

    public function task()
    {
        return $this->belongsTo(TeamTask::class, 'team_task_id', 'team_task_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to', 'user_id');
    }
}
