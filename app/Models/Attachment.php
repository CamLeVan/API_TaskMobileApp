<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $table = 'attachments';
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'attachable_id',
        'attachable_type',
        'filename',
        'original_filename',
        'path',
        'mime_type',
        'size',
        'is_temp'
    ];

    protected $casts = [
        'size' => 'integer',
        'is_temp' => 'boolean'
    ];

    /**
     * Get the user that owns the attachment.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the parent attachable model.
     */
    public function attachable()
    {
        return $this->morphTo();
    }
}
