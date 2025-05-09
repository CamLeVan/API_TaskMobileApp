<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentUserPermission;
use App\Models\DocumentVersion;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class DocumentController extends Controller
{
    /**
     * Get a list of documents for a team
     */
    public function index(Request $request, Team $team)
    {
        // Verify user is a member of the team
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = Document::where('team_id', $team->id);

        // Filter by folder
        if ($request->has('folder_id')) {
            $query->where('folder_id', $request->folder_id);
        }

        // Search by name or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by file type
        if ($request->has('file_type')) {
            $query->where('file_type', 'like', "%{$request->file_type}%");
        }

        // Sort
        $sortBy = $request->input('sort_by', 'updated_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Paginate
        $perPage = $request->input('per_page', 20);
        $documents = $query->with(['uploader', 'folder'])->paginate($perPage);

        return response()->json($documents);
    }

    /**
     * Upload a new document
     */
    public function store(Request $request, Team $team)
    {
        // Verify user is a member of the team
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:102400', // 100MB max
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'folder_id' => 'nullable|exists:document_folders,id',
            'access_level' => 'nullable|in:public,team,private,specific_users',
            'allowed_users' => 'nullable|array',
            'allowed_users.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Handle file upload
        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $fileType = $file->getMimeType();
        $fileSize = $file->getSize();

        // Generate a unique file name
        $uniqueFileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath = "teams/{$team->id}/documents/{$uniqueFileName}";

        // Store the file
        Storage::put($filePath, file_get_contents($file));

        // Create thumbnail if it's an image
        $thumbnailPath = null;
        if (Str::startsWith($fileType, 'image/')) {
            $thumbnailPath = "teams/{$team->id}/documents/thumbnails/{$uniqueFileName}";
            $thumbnail = Image::make($file)->resize(200, 200, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            Storage::put($thumbnailPath, (string) $thumbnail->encode());
        }

        // Create document record
        $document = new Document([
            'name' => $request->input('name', $fileName),
            'description' => $request->input('description'),
            'file_path' => $filePath,
            'thumbnail_path' => $thumbnailPath,
            'file_type' => $fileType,
            'file_size' => $fileSize,
            'folder_id' => $request->input('folder_id'),
            'team_id' => $team->id,
            'uploaded_by' => Auth::id(),
            'access_level' => $request->input('access_level', 'team'),
            'current_version' => 1
        ]);

        $document->save();

        // Create initial version
        $version = new DocumentVersion([
            'document_id' => $document->id,
            'version_number' => 1,
            'file_path' => $filePath,
            'thumbnail_path' => $thumbnailPath,
            'file_size' => $fileSize,
            'created_by' => Auth::id(),
            'version_note' => 'Initial version'
        ]);

        $version->save();

        // Add allowed users if access level is specific_users
        if ($request->input('access_level') === 'specific_users' && $request->has('allowed_users')) {
            foreach ($request->input('allowed_users') as $userId) {
                DocumentUserPermission::create([
                    'document_id' => $document->id,
                    'user_id' => $userId
                ]);
            }
        }

        // Load relationships
        $document->load(['uploader', 'folder', 'allowedUsers']);

        return response()->json(['data' => $document], 201);
    }

    /**
     * Get a specific document
     */
    public function show(Request $request, Document $document)
    {
        // Check if user can access this document
        if (!$document->isAccessibleBy(Auth::user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Load relationships
        $document->load([
            'uploader',
            'folder',
            'allowedUsers',
            'versions' => function ($query) {
                $query->with('creator')->orderBy('version_number', 'desc');
            }
        ]);

        return response()->json(['data' => $document]);
    }

    /**
     * Update document information
     */
    public function update(Request $request, Document $document)
    {
        // Check if user can access this document
        if ($document->uploaded_by !== Auth::id() &&
            !$document->team->members()->where('user_id', Auth::id())->where('role', 'manager')->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'folder_id' => 'nullable|exists:document_folders,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update document
        if ($request->has('name')) {
            $document->name = $request->name;
        }

        if ($request->has('description')) {
            $document->description = $request->description;
        }

        if ($request->has('folder_id')) {
            $document->folder_id = $request->folder_id;
        }

        $document->save();

        // Load relationships
        $document->load(['uploader', 'folder']);

        return response()->json(['data' => $document]);
    }

    /**
     * Delete a document
     */
    public function destroy(Document $document)
    {
        // Check if user can delete this document
        if ($document->uploaded_by !== Auth::id() &&
            !$document->team->members()->where('user_id', Auth::id())->where('role', 'manager')->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete file and thumbnail
        Storage::delete($document->file_path);
        if ($document->thumbnail_path) {
            Storage::delete($document->thumbnail_path);
        }

        // Delete all versions
        foreach ($document->versions as $version) {
            Storage::delete($version->file_path);
            if ($version->thumbnail_path) {
                Storage::delete($version->thumbnail_path);
            }
        }

        // Delete document and related records (versions and permissions will be deleted by cascade)
        $document->delete();

        return response()->json(['message' => 'Document deleted successfully']);
    }

    /**
     * Download a document
     */
    public function download(Document $document)
    {
        // Check if user can access this document
        if (!$document->isAccessibleBy(Auth::user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Get the file path
        $filePath = $document->file_path;

        // Check if file exists
        if (!Storage::exists($filePath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        // Return file for download
        return Storage::download($filePath, $document->name);
    }

    /**
     * Update document access permissions
     */
    public function updateAccess(Request $request, Document $document)
    {
        // Check if user can update this document
        if ($document->uploaded_by !== Auth::id() &&
            !$document->team->members()->where('user_id', Auth::id())->where('role', 'manager')->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'access_level' => 'required|in:public,team,private,specific_users',
            'allowed_users' => 'required_if:access_level,specific_users|array',
            'allowed_users.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update access level
        $document->access_level = $request->access_level;
        $document->save();

        // Update allowed users if access level is specific_users
        if ($request->access_level === 'specific_users') {
            // Delete existing permissions
            DocumentUserPermission::where('document_id', $document->id)->delete();

            // Add new permissions
            foreach ($request->allowed_users as $userId) {
                DocumentUserPermission::create([
                    'document_id' => $document->id,
                    'user_id' => $userId
                ]);
            }
        } else {
            // Delete all permissions if access level is not specific_users
            DocumentUserPermission::where('document_id', $document->id)->delete();
        }

        // Load allowed users
        $document->load('allowedUsers');

        return response()->json([
            'data' => [
                'id' => $document->id,
                'access_level' => $document->access_level,
                'allowed_users' => $document->allowedUsers
            ]
        ]);
    }
}
