<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KanbanColumn extends Model
{
    use HasFactory;

    protected $table = 'kanban_columns';
    protected $primaryKey = 'id';

    protected $fillable = [
        'team_id',
        'name',
        'order',
        'color',
        'is_default'
    ];

    protected $casts = [
        'order' => 'integer',
        'is_default' => 'boolean'
    ];

    /**
     * Get the team that owns the column.
     */
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }

    /**
     * Get the tasks in this column.
     */
    public function tasks()
    {
        return $this->hasMany(TeamTask::class, 'column_id', 'id')->orderBy('order');
    }
}
