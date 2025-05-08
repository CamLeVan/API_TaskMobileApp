<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    use HasFactory;

    protected $table = 'device_tokens';
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'device_id',
        'token',
        'device_name',
        'platform',
        'last_used_at'
    ];

    protected $casts = [
        'last_used_at' => 'datetime'
    ];

    /**
     * Get the user that owns the device token.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
