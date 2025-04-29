<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamTask extends Model
{
    use HasFactory;

    protected $table = 'team_tasks';
    protected $primaryKey = 'id';

    protected $fillable = [
        'team_id',
        'created_by',
        'title',
        'description',
        'deadline',
        'priority',
        'status',
        'order'
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'priority' => 'integer',
        'order' => 'integer'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function assignments()
    {
        return $this->hasMany(TeamTaskAssignment::class, 'team_task_id', 'id');
    }
}
