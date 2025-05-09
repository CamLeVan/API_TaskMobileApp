<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'device_id',
        'platform'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 