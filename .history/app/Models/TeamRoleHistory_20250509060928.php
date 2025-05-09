<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamRoleHistory extends Model
{
    use HasFactory;

    protected $table = 'team_role_history';
    protected $primaryKey = 'id';

    protected $fillable = [
        'team_id',
        'user_id',
        'changed_by',
        'old_role',
        'new_role',
        'reason'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by', 'id');
    }
}
