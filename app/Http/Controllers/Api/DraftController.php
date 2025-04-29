<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Draft;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DraftController extends Controller
{
    /**
     * Get all drafts for the authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $type = $request->get('type');
        $parentId = $request->get('parent_id');
        
        $query = Draft::where('user_id', $user->id);
        
        if ($type) {
            $query->where('type', $type);
        }
        
        if ($parentId) {
            $query->where('parent_id', $parentId);
        }
        
        $drafts = $query->get();
        
        return response()->json($drafts);
    }
    
    /**
     * Save or update a draft
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:personal_task,team_task,message',
            'content' => 'required|array',
            'parent_id' => 'nullable|integer'
        ]);
        
        $user = $request->user();
        
        // Lưu hoặc cập nhật nháp
        $draft = Draft::updateOrCreate(
            [
                'user_id' => $user->id,
                'type' => $request->type,
                'parent_id' => $request->parent_id
            ],
            [
                'content' => $request->content,
                'updated_at' => now()
            ]
        );
        
        return response()->json($draft);
    }
    
    /**
     * Get a specific draft
     */
    public function show(Request $request, Draft $draft)
    {
        if ($draft->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return response()->json($draft);
    }
    
    /**
     * Delete a draft
     */
    public function destroy(Request $request, Draft $draft)
    {
        if ($draft->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $draft->delete();
        
        return response()->json(null, 204);
    }
}
