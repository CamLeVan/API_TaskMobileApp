<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DocumentFolderController extends Controller
{
    /**
     * Get a list of folders for a team
     */
    public function index(Request $request, Team $team)
    {
        // Verify user is a member of the team
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = DocumentFolder::where('team_id', $team->id);

        // Filter by parent folder
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        } else {
            $query->whereNull('parent_id'); // Root folders by default
        }

        // Get folders with counts
        $folders = $query->withCount(['documents', 'subfolders'])->get();

        return response()->json(['data' => $folders]);
    }

    /**
     * Create a new folder
     */
    public function store(Request $request, Team $team)
    {
        // Verify user is a member of the team
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:document_folders,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verify parent folder belongs to the same team if provided
        if ($request->has('parent_id')) {
            $parentFolder = DocumentFolder::find($request->parent_id);
            if (!$parentFolder || $parentFolder->team_id !== $team->id) {
                return response()->json(['message' => 'Invalid parent folder'], 422);
            }
        }

        // Create folder
        $folder = new DocumentFolder([
            'name' => $request->name,
            'description' => $request->description,
            'parent_id' => $request->parent_id,
            'team_id' => $team->id,
            'created_by' => Auth::id()
        ]);

        $folder->save();

        // Load relationships
        $folder->load('creator');
        $folder->loadCount(['documents', 'subfolders']);

        return response()->json(['data' => $folder], 201);
    }

    /**
     * Get a specific folder
     */
    public function show(DocumentFolder $folder)
    {
        // Verify user is a member of the team
        if (!$folder->team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Load relationships and counts
        $folder->load(['creator', 'parent']);
        $folder->loadCount(['documents', 'subfolders']);

        return response()->json(['data' => $folder]);
    }

    /**
     * Update a folder
     */
    public function update(Request $request, DocumentFolder $folder)
    {
        // Verify user is a member of the team
        if (!$folder->team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update folder
        if ($request->has('name')) {
            $folder->name = $request->name;
        }

        if ($request->has('description')) {
            $folder->description = $request->description;
        }

        $folder->save();

        // Load relationships and counts
        $folder->load(['creator', 'parent']);
        $folder->loadCount(['documents', 'subfolders']);

        return response()->json(['data' => $folder]);
    }

    /**
     * Delete a folder
     */
    public function destroy(Request $request, DocumentFolder $folder)
    {
        // Verify user is a member of the team
        if (!$folder->team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if we should delete contents
        $deleteContents = $request->input('delete_contents', false);

        if ($deleteContents) {
            // Delete all documents in this folder and subfolders
            $documents = $folder->getAllDocuments();
            foreach ($documents as $document) {
                // Delete file and thumbnail
                if ($document->file_path) {
                    \Storage::delete($document->file_path);
                }
                if ($document->thumbnail_path) {
                    \Storage::delete($document->thumbnail_path);
                }

                // Delete all versions
                foreach ($document->versions as $version) {
                    if ($version->file_path) {
                        \Storage::delete($version->file_path);
                    }
                    if ($version->thumbnail_path) {
                        \Storage::delete($version->thumbnail_path);
                    }
                }

                // Delete document (cascade will delete versions and permissions)
                $document->delete();
            }

            // Delete folder and all subfolders (cascade)
            $folder->delete();
        } else {
            // Move documents to parent folder or root
            $parentId = $folder->parent_id;
            Document::where('folder_id', $folder->id)->update(['folder_id' => $parentId]);

            // Move subfolders to parent folder or root
            DocumentFolder::where('parent_id', $folder->id)->update(['parent_id' => $parentId]);

            // Delete folder
            $folder->delete();
        }

        return response()->json(['message' => 'Folder deleted successfully']);
    }
}
