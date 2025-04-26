<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncStatus extends Model
{
    use HasFactory;
    
    protected $table = 'sync_status';
    
    protected $fillable = [
        'user_id',
        'device_id',
        'last_synced_at'
    ];
    
    protected $casts = [
        'last_synced_at' => 'datetime'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 