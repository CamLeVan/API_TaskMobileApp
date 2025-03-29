<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $table = 'teams';
    protected $primaryKey = 'team_id';

    protected $fillable = [
        'name',
        'description',
        'created_by'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function members()
    {
        return $this->hasMany(TeamMember::class, 'team_id', 'team_id');
    }

    public function tasks()
    {
        return $this->hasMany(TeamTask::class, 'team_id', 'team_id');
    }

    public function chatMessages()
    {
        return $this->hasMany(GroupChatMessage::class, 'team_id', 'team_id');
    }
}
