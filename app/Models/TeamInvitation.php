<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamInvitation extends Model
{
    use HasFactory;

    protected $table = 'team_invitations';
    protected $primaryKey = 'id';

    protected $fillable = [
        'team_id',
        'created_by',
        'email',
        'role',
        'token',
        'status',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Kiểm tra xem lời mời có hết hạn không
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Kiểm tra xem lời mời có đang chờ xử lý không
     */
    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * Đánh dấu lời mời là đã chấp nhận
     */
    public function markAsAccepted(): void
    {
        $this->status = 'accepted';
        $this->save();
    }

    /**
     * Đánh dấu lời mời là đã từ chối
     */
    public function markAsRejected(): void
    {
        $this->status = 'rejected';
        $this->save();
    }

    /**
     * Đánh dấu lời mời là đã hết hạn
     */
    public function markAsExpired(): void
    {
        $this->status = 'expired';
        $this->save();
    }

    /**
     * Quan hệ với nhóm
     */
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }

    /**
     * Quan hệ với người tạo lời mời
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
