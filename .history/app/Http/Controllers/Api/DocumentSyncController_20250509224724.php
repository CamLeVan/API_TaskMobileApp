<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DocumentSyncController extends Controller
{
    /**
     * Get documents that have changed since last sync
     */
    public function getChanges(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'last_sync_at' => 'required|date',
            'team_id' => 'nullable|exists:teams,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lastSyncAt = Carbon::parse($request->last_sync_at);
        $userId = Auth::id();

        // Base query for documents the user can access
        $query = Document::where(function ($q) use ($userId) {
            // Public documents
            $q->where('access_level', 'public');

            // Team documents for teams the user is a member of
            $q->orWhereHas('team', function ($q) use ($userId) {
                $q->whereHas('members', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            })->where('access_level', 'team');

            // Private documents owned by the user
            $q->orWhere(function ($q) use ($userId) {
                $q->where('access_level', 'private')
                  ->where('uploaded_by', $userId);
            });

            // Documents shared with the user specifically
            $q->orWhere(function ($q) use ($userId) {
                $q->where('access_level', 'specific_users')
                  ->whereHas('allowedUsers', function ($q) use ($userId) {
                      $q->where('user_id', $userId);
                  });
            });
        });

        // Filter by team if provided
        if ($request->has('team_id')) {
            $query->where('team_id', $request->team_id);
        }

        // Get updated documents
        $updatedDocuments = (clone $query)
            ->where(function ($q) use ($lastSyncAt) {
                $q->where('updated_at', '>', $lastSyncAt)
                  ->orWhereHas('versions', function ($q) use ($lastSyncAt) {
                      $q->where('created_at', '>', $lastSyncAt);
                  });
            })
            ->with(['uploader', 'folder'])
            ->get();

        // Get deleted documents (using soft deletes)
        $deletedDocumentIds = (clone $query)
            ->onlyTrashed()
            ->where('deleted_at', '>', $lastSyncAt)
            ->pluck('id');

        return response()->json([
            'data' => [
                'updated' => $updatedDocuments,
                'deleted' => $deletedDocumentIds
            ]
        ]);
    }

    /**
     * Resolve conflicts between client and server
     */
    public function resolveConflicts(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'conflicts' => 'required|array',
            'conflicts.*.document_id' => 'required|exists:documents,id',
            'conflicts.*.resolution' => 'required|in:server,client',
            'conflicts.*.client_data' => 'required_if:conflicts.*.resolution,client|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $resolvedIds = [];

        foreach ($request->conflicts as $conflict) {
            $document = Document::find($conflict['document_id']);

            // Skip if document not found or user can't access it
            if (!$document || !$document->isAccessibleBy(Auth::user())) {
                continue;
            }

            // Skip if user can't update the document
            if ($document->uploaded_by !== Auth::id() &&
                !$document->team->members()->where('user_id', Auth::id())->where('role', 'manager')->exists()) {
                continue;
            }

            if ($conflict['resolution'] === 'client' && isset($conflict['client_data'])) {
                // Update document with client data
                $clientData = $conflict['client_data'];

                if (isset($clientData['name'])) {
                    $document->name = $clientData['name'];
                }

                if (isset($clientData['description'])) {
                    $document->description = $clientData['description'];
                }

                if (isset($clientData['folder_id'])) {
                    $document->folder_id = $clientData['folder_id'];
                }

                $document->save();
            }

            $resolvedIds[] = $document->id;
        }

        return response()->json([
            'message' => 'Conflicts resolved successfully',
            'data' => [
                'resolved' => $resolvedIds
            ]
        ]);
    }
}
