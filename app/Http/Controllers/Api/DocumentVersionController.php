<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class DocumentVersionController extends Controller
{
    /**
     * Get all versions of a document
     */
    public function index(Document $document)
    {
        // Check if user can access this document
        if (!$document->isAccessibleBy(Auth::user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Get all versions with creator info
        $versions = $document->versions()->with('creator')->orderBy('version_number', 'desc')->get();

        return response()->json(['data' => $versions]);
    }

    /**
     * Get a specific version of a document
     */
    public function show(Document $document, $versionNumber)
    {
        // Check if user can access this document
        if (!$document->isAccessibleBy(Auth::user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Find the version
        $version = $document->versions()->where('version_number', $versionNumber)->with('creator')->first();

        if (!$version) {
            return response()->json(['message' => 'Version not found'], 404);
        }

        return response()->json(['data' => $version]);
    }

    /**
     * Upload a new version of a document
     */
    public function store(Request $request, Document $document)
    {
        // Check if user can update this document
        if ($document->uploaded_by !== Auth::id() &&
            !$document->team->members()->where('user_id', Auth::id())->where('role', 'manager')->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:102400', // 100MB max
            'version_note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Handle file upload
        $file = $request->file('file');
        $fileType = $file->getMimeType();
        $fileSize = $file->getSize();

        // Generate a unique file name
        $uniqueFileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath = "teams/{$document->team_id}/documents/{$uniqueFileName}";

        // Store the file
        Storage::put($filePath, file_get_contents($file));

        // Create thumbnail if it's an image
        $thumbnailPath = null;
        if (Str::startsWith($fileType, 'image/')) {
            $thumbnailPath = "teams/{$document->team_id}/documents/thumbnails/{$uniqueFileName}";
            $thumbnail = Image::make($file)->resize(200, 200, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            Storage::put($thumbnailPath, (string) $thumbnail->encode());
        }

        // Increment version number
        $newVersionNumber = $document->current_version + 1;

        // Create new version
        $version = new DocumentVersion([
            'document_id' => $document->id,
            'version_number' => $newVersionNumber,
            'file_path' => $filePath,
            'thumbnail_path' => $thumbnailPath,
            'file_size' => $fileSize,
            'created_by' => Auth::id(),
            'version_note' => $request->input('version_note', "Version {$newVersionNumber}")
        ]);

        $version->save();

        // Update document
        $document->file_path = $filePath;
        $document->thumbnail_path = $thumbnailPath;
        $document->file_type = $fileType;
        $document->file_size = $fileSize;
        $document->current_version = $newVersionNumber;
        $document->save();

        // Load relationships
        $version->load('creator');

        return response()->json(['data' => $version]);
    }

    /**
     * Download a specific version of a document
     */
    public function download(Document $document, $versionNumber)
    {
        // Check if user can access this document
        if (!$document->isAccessibleBy(Auth::user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Find the version
        $version = $document->versions()->where('version_number', $versionNumber)->first();

        if (!$version) {
            return response()->json(['message' => 'Version not found'], 404);
        }

        // Get the file path
        $filePath = $version->file_path;

        // Check if file exists
        if (!Storage::exists($filePath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        // Return file for download
        return Storage::download($filePath, $document->name);
    }

    /**
     * Restore a previous version as the current version
     */
    public function restore(Document $document, $versionNumber)
    {
        // Check if user can update this document
        if ($document->uploaded_by !== Auth::id() &&
            !$document->team->members()->where('user_id', Auth::id())->where('role', 'manager')->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Find the version
        $version = $document->versions()->where('version_number', $versionNumber)->first();

        if (!$version) {
            return response()->json(['message' => 'Version not found'], 404);
        }

        // Update document to use this version
        $document->file_path = $version->file_path;
        $document->thumbnail_path = $version->thumbnail_path;
        $document->file_size = $version->file_size;
        $document->current_version = $version->version_number;
        $document->save();

        return response()->json(['message' => 'Version restored successfully', 'data' => $document]);
    }
}
