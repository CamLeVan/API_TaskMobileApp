<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $table = 'teams';
    // Sử dụng khóa mặc định là 'id'
    // protected $primaryKey = 'id'; // Không cần khai báo nếu là 'id'

    protected $fillable = [
        'name',
        'description',
        'created_by'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function members()
    {
        // Sử dụng 'id' của bảng teams làm khóa chính
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
}
