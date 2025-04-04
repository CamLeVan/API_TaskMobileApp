<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupChatMessage extends Model
{
    use HasFactory;

    protected $table = 'group_chat_messages';
    protected $primaryKey = 'id';

    protected $fillable = [
        'team_id',
        'sender_id',
        'message',
        'file_url',
        'timestamp'
    ];

    protected $casts = [
        'timestamp' => 'datetime'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id', 'id');
    }

    public function readStatuses()
    {
        return $this->hasMany(MessageReadStatus::class, 'message_id', 'id');
    }
}
