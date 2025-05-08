<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $table = 'tasks';
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'team_id',
        'title',
        'description',
        'deadline',
        'priority',
        'status',
        'completed_at',
        'column_id',
        'order'
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'completed_at' => 'datetime',
        'priority' => 'integer',
        'order' => 'integer'
    ];

    /**
     * Get the user that owns the task.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the team that owns the task.
     */
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }

    /**
     * Get the column that contains the task.
     */
    public function column()
    {
        return $this->belongsTo(KanbanColumn::class, 'column_id', 'id');
    }

    /**
     * Get the assignments for the task.
     */
    public function assignments()
    {
        return $this->hasMany(TeamTaskAssignment::class, 'team_task_id', 'id');
    }

    /**
     * Get the labels for the task.
     */
    public function labels()
    {
        return $this->belongsToMany(Label::class, 'task_labels', 'task_id', 'label_id')
                    ->withTimestamps();
    }

    /**
     * Get the subtasks for the task.
     */
    public function subtasks()
    {
        return $this->morphMany(Subtask::class, 'taskable')->orderBy('order');
    }
}
