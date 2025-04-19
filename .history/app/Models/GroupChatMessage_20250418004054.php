<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupChatMessage extends Model
{
    use HasFactory;

    const STATUS_SENDING = 'sending';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';

    protected $table = 'group_chat_messages';
    protected $primaryKey = 'id';

    protected $fillable = [
        'team_id',
        'sender_id',
        'message',
        'file_url',
        'status',
        'client_temp_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function readStatuses(): HasMany
    {
        return $this->hasMany(MessageReadStatus::class, 'message_id');
    }
}
