<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subtask extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'completed',
        'order'
    ];

    protected $casts = [
        'completed' => 'boolean',
        'order' => 'integer'
    ];

    /**
     * Get the parent taskable model (PersonalTask or TeamTask).
     */
    public function taskable()
    {
        return $this->morphTo();
    }
}
