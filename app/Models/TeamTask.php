<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamTask extends Model
{
    use HasFactory;

    protected $table = 'team_tasks';
    protected $primaryKey = 'team_task_id';

    protected $fillable = [
        'team_id',
        'created_by',
        'title',
        'description',
        'deadline',
        'priority',
        'status'
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'priority' => 'integer'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function assignments()
    {
        return $this->hasMany(TeamTaskAssignment::class, 'team_task_id', 'team_task_id');
    }
}
