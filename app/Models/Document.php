<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'file_path',
        'thumbnail_path',
        'file_type',
        'file_size',
        'folder_id',
        'team_id',
        'uploaded_by',
        'access_level',
        'current_version'
    ];

    protected $appends = [
        'file_url',
        'thumbnail_url'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'current_version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function folder()
    {
        return $this->belongsTo(DocumentFolder::class, 'folder_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function versions()
    {
        return $this->hasMany(DocumentVersion::class)->orderBy('version_number', 'desc');
    }

    public function currentVersion()
    {
        return $this->hasOne(DocumentVersion::class)->where('version_number', $this->current_version);
    }

    public function allowedUsers()
    {
        return $this->belongsToMany(User::class, 'document_user_permissions', 'document_id', 'user_id')
            ->withTimestamps();
    }

    public function getFileUrlAttribute()
    {
        if ($this->file_path) {
            return url(Storage::url($this->file_path));
        }
        return null;
    }

    public function getThumbnailUrlAttribute()
    {
        if ($this->thumbnail_path) {
            return url(Storage::url($this->thumbnail_path));
        }
        return null;
    }

    public function isAccessibleBy(User $user)
    {
        // Public documents are accessible by anyone
        if ($this->access_level === 'public') {
            return true;
        }

        // Team documents are accessible by team members
        if ($this->access_level === 'team') {
            return $this->team->members()->where('user_id', $user->id)->exists();
        }

        // Private documents are only accessible by the uploader
        if ($this->access_level === 'private') {
            return $this->uploaded_by === $user->id;
        }

        // Specific users access
        if ($this->access_level === 'specific_users') {
            return $this->allowedUsers()->where('user_id', $user->id)->exists() ||
                   $this->uploaded_by === $user->id;
        }

        return false;
    }
}
