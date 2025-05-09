<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageReadStatus extends Model
{
    use HasFactory;

    protected $table = 'message_read_status';
    protected $primaryKey = 'id';

    protected $fillable = [
        'message_id',
        'user_id'
    ];

    protected $casts = [
        'read_at' => 'datetime'
    ];

    public function message()
    {
        return $this->belongsTo(GroupChatMessage::class, 'message_id', 'message_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
