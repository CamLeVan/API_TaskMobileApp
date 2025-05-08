<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Label;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LabelController extends Controller
{
    /**
     * Lấy danh sách nhãn của nhóm
     */
    public function index(Team $team)
    {
        // Kiểm tra quyền truy cập
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return response()->json([
            'data' => $team->labels()->orderBy('name')->get()
        ]);
    }
    
    /**
     * Tạo nhãn mới
     */
    public function store(Request $request, Team $team)
    {
        // Kiểm tra quyền truy cập
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:30',
            'color' => 'required|string|max:7',
            'description' => 'nullable|string|max:255'
        ]);
        
        $label = $team->labels()->create([
            'name' => $validated['name'],
            'color' => $validated['color'],
            'description' => $validated['description'] ?? null,
            'created_by' => Auth::id()
        ]);
        
        return response()->json([
            'data' => $label
        ], 201);
    }
    
    /**
     * Cập nhật nhãn
     */
    public function update(Request $request, Team $team, Label $label)
    {
        // Kiểm tra quyền truy cập và xác nhận label thuộc team
        if (!$team->members()->where('user_id', Auth::id())->exists() || 
            $label->team_id !== $team->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:30',
            'color' => 'sometimes|string|max:7',
            'description' => 'nullable|string|max:255'
        ]);
        
        $label->update($validated);
        
        return response()->json([
            'data' => $label
        ]);
    }
    
    /**
     * Xóa nhãn
     */
    public function destroy(Team $team, Label $label)
    {
        // Kiểm tra quyền truy cập và xác nhận label thuộc team
        if (!$team->members()->where('user_id', Auth::id())->exists() || 
            $label->team_id !== $team->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Xóa liên kết với các task
        $label->tasks()->detach();
        
        $label->delete();
        
        return response()->json(null, 204);
    }
    
    /**
     * Gán nhãn cho task
     */
    public function attachToTask(Request $request, Team $team, Task $task)
    {
        // Kiểm tra quyền truy cập và xác nhận task thuộc team
        if (!$team->members()->where('user_id', Auth::id())->exists() || 
            $task->team_id !== $team->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'label_id' => 'required|integer|exists:labels,id'
        ]);
        
        $label = Label::find($validated['label_id']);
        
        // Kiểm tra label thuộc team
        if ($label->team_id !== $team->id) {
            return response()->json(['message' => 'Label does not belong to this team'], 422);
        }
        
        // Gán nhãn nếu chưa có
        if (!$task->labels()->where('label_id', $label->id)->exists()) {
            $task->labels()->attach($label->id);
        }
        
        return response()->json([
            'data' => $task->labels
        ]);
    }
    
    /**
     * Gỡ nhãn khỏi task
     */
    public function detachFromTask(Request $request, Team $team, Task $task, Label $label)
    {
        // Kiểm tra quyền truy cập và xác nhận task và label thuộc team
        if (!$team->members()->where('user_id', Auth::id())->exists() || 
            $task->team_id !== $team->id ||
            $label->team_id !== $team->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $task->labels()->detach($label->id);
        
        return response()->json([
            'data' => $task->labels
        ]);
    }
}