<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Label extends Model
{
    use HasFactory;

    protected $table = 'labels';
    protected $primaryKey = 'id';

    protected $fillable = [
        'team_id',
        'name',
        'color',
        'description'
    ];

    /**
     * Get the team that owns the label.
     */
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }

    /**
     * Get the tasks that have this label.
     */
    public function tasks()
    {
        return $this->belongsToMany(TeamTask::class, 'task_labels', 'label_id', 'task_id')
                    ->withTimestamps();
    }
}
