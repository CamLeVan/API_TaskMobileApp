<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    use HasFactory;

    protected $table = 'team_members';
    protected $primaryKey = 'id';

    protected $fillable = [
        'team_id',
        'user_id',
        'role'
    ];

    protected $casts = [
        'joined_at' => 'datetime'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function taskAssignments()
    {
        return $this->hasMany(TeamTaskAssignment::class, 'assigned_to', 'user_id');
    }
}
