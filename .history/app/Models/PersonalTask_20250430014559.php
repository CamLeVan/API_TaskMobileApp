<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalTask extends Model
{
    use HasFactory;

    protected $table = 'personal_tasks';
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'deadline',
        'priority',
        'status',
        'order'
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'priority' => 'integer',
        'order' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
