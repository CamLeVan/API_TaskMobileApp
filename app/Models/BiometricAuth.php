<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BiometricAuth extends Model
{
    use HasFactory;

    protected $table = 'biometric_auth';

    protected $fillable = [
        'user_id',
        'device_id',
        'biometric_token',
        'last_used_at'
    ];

    protected $casts = [
        'last_used_at' => 'datetime'
    ];

    /**
     * Get the user that owns the biometric authentication.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
