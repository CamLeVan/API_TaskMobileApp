<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Draft extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'parent_id',
        'content'
    ];

    protected $casts = [
        'content' => 'array',
        'parent_id' => 'integer'
    ];

    /**
     * Get the user that owns the draft.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
