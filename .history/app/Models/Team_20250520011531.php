<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Team extends Model
{
    use HasFactory;

    protected $table = 'teams';
    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'description',
        'created_by',
        'uuid'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function members()
    {
        return $this->hasMany(TeamMember::class, 'team_id', 'id');
    }

    public function tasks()
    {
        return $this->hasMany(TeamTask::class, 'team_id', 'id');
    }

    public function chatMessages()
    {
        return $this->hasMany(GroupChatMessage::class, 'team_id', 'id');
    }

    /**
     * Quan hệ với lời mời nhóm
     */
    public function invitations()
    {
        return $this->hasMany(TeamInvitation::class, 'team_id', 'id');
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($team) {
            // Tự động tạo UUID nếu chưa có
            if (empty($team->uuid)) {
                $team->uuid = Str::uuid()->toString();
            }
        });
    }
}
