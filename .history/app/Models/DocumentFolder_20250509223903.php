<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentFolder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'parent_id',
        'team_id',
        'created_by'
    ];

    protected $appends = [
        'document_count',
        'subfolder_count'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parent()
    {
        return $this->belongsTo(DocumentFolder::class, 'parent_id');
    }

    public function subfolders()
    {
        return $this->hasMany(DocumentFolder::class, 'parent_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'folder_id');
    }

    public function getDocumentCountAttribute()
    {
        return $this->documents()->count();
    }

    public function getSubfolderCountAttribute()
    {
        return $this->subfolders()->count();
    }

    public function getAllDocuments()
    {
        $documents = $this->documents;

        foreach ($this->subfolders as $subfolder) {
            $documents = $documents->merge($subfolder->getAllDocuments());
        }

        return $documents;
    }
}
