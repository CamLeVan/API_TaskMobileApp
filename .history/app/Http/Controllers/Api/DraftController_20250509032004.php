<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Draft;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DraftController extends Controller
{
    /**
     * Lấy danh sách nháp
     */
    public function index()
    {
        $user = Auth::user();
        $drafts = $user->drafts()->orderBy('updated_at', 'desc')->get();
        
        return response()->json([
            'data' => $drafts
        ]);
    }
    
    /**
     * Lấy chi tiết nháp
     */
    public function show($id)
    {
        $user = Auth::user();
        $draft = $user->drafts()->findOrFail($id);
        
        return response()->json([
            'data' => $draft
        ]);
    }
    
    /**
     * Tạo nháp mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:personal_task,team_task,message',
            'content' => 'required|json',
            'team_id' => 'nullable|exists:teams,id',
            'device_id' => 'required|string'
        ]);
        
        $user = Auth::user();
        
        // Kiểm tra quyền truy cập team nếu có
        if (isset($validated['team_id'])) {
            $teamExists = $user->teams()->where('teams.id', $validated['team_id'])->exists();
            
            if (!$teamExists) {
                return response()->json([
                    'message' => 'You do not have access to this team'
                ], 403);
            }
        }
        
        $draft = $user->drafts()->create([
            'type' => $validated['type'],
            'content' => $validated['content'],
            'team_id' => $validated['team_id'],
            'device_id' => $validated['device_id']
        ]);
        
        return response()->json([
            'data' => $draft,
            'message' => 'Draft created successfully'
        ], 201);
    }
    
    /**
     * Cập nhật nháp
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'content' => 'required|json',
            'team_id' => 'nullable|exists:teams,id'
        ]);
        
        $user = Auth::user();
        $draft = $user->drafts()->findOrFail($id);
        
        // Kiểm tra quyền truy cập team nếu có
        if (isset($validated['team_id'])) {
            $teamExists = $user->teams()->where('teams.id', $validated['team_id'])->exists();
            
            if (!$teamExists) {
                return response()->json([
                    'message' => 'You do not have access to this team'
                ], 403);
            }
        }
        
        $draft->update([
            'content' => $validated['content'],
            'team_id' => $validated['team_id'] ?? $draft->team_id
        ]);
        
        return response()->json([
            'data' => $draft,
            'message' => 'Draft updated successfully'
        ]);
    }
    
    /**
     * Xóa nháp
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $draft = $user->drafts()->findOrFail($id);
        
        $draft->delete();
        
        return response()->json([
            'message' => 'Draft deleted successfully'
        ]);
    }
    
    /**
     * Lấy nháp theo thiết bị
     */
    public function getByDevice(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|string'
        ]);
        
        $user = Auth::user();
        $deviceId = $validated['device_id'];
        
        $drafts = $user->drafts()
            ->where('device_id', $deviceId)
            ->orderBy('updated_at', 'desc')
            ->get();
            
        return response()->json([
            'data' => $drafts
        ]);
    }
    
    /**
     * Lấy nháp theo loại
     */
    public function getByType(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:personal_task,team_task,message'
        ]);
        
        $user = Auth::user();
        $type = $validated['type'];
        
        $drafts = $user->drafts()
            ->where('type', $type)
            ->orderBy('updated_at', 'desc')
            ->get();
            
        return response()->json([
            'data' => $drafts
        ]);
    }
    
    /**
     * Lấy nháp theo nhóm
     */
    public function getByTeam(Request $request, $teamId)
    {
        $user = Auth::user();
        
        // Kiểm tra quyền truy cập team
        $teamExists = $user->teams()->where('teams.id', $teamId)->exists();
        
        if (!$teamExists) {
            return response()->json([
                'message' => 'You do not have access to this team'
            ], 403);
        }
        
        $drafts = $user->drafts()
            ->where('team_id', $teamId)
            ->orderBy('updated_at', 'desc')
            ->get();
            
        return response()->json([
            'data' => $drafts
        ]);
    }
    
    /**
     * Tự động lưu nháp
     */
    public function autoSave(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:personal_task,team_task,message',
            'content' => 'required|json',
            'team_id' => 'nullable|exists:teams,id',
            'device_id' => 'required|string'
        ]);
        
        $user = Auth::user();
        $deviceId = $validated['device_id'];
        $type = $validated['type'];
        
        // Kiểm tra quyền truy cập team nếu có
        if (isset($validated['team_id'])) {
            $teamExists = $user->teams()->where('teams.id', $validated['team_id'])->exists();
            
            if (!$teamExists) {
                return response()->json([
                    'message' => 'You do not have access to this team'
                ], 403);
            }
        }
        
        // Tìm nháp hiện có hoặc tạo mới
        $draft = $user->drafts()
            ->where('device_id', $deviceId)
            ->where('type', $type)
            ->where('team_id', $validated['team_id'])
            ->first();
            
        if ($draft) {
            $draft->update([
                'content' => $validated['content']
            ]);
            
            $message = 'Draft updated automatically';
        } else {
            $draft = $user->drafts()->create([
                'type' => $type,
                'content' => $validated['content'],
                'team_id' => $validated['team_id'],
                'device_id' => $deviceId
            ]);
            
            $message = 'Draft saved automatically';
        }
        
        return response()->json([
            'data' => $draft,
            'message' => $message
        ]);
    }
}

