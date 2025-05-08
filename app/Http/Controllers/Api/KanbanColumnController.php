<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\KanbanColumn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KanbanColumnController extends Controller
{
    /**
     * Lấy danh sách cột Kanban của nhóm
     */
    public function index(Team $team)
    {
        // Kiểm tra quyền truy cập
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return response()->json([
            'data' => $team->kanbanColumns()->orderBy('order')->get()
        ]);
    }
    
    /**
     * Tạo cột Kanban mới
     */
    public function store(Request $request, Team $team)
    {
        // Kiểm tra quyền truy cập (chỉ admin nhóm mới được tạo cột)
        if (!$team->members()->where('user_id', Auth::id())->where('role', 'admin')->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer',
            'is_default' => 'nullable|boolean'
        ]);
        
        // Nếu đánh dấu là cột mặc định, bỏ đánh dấu các cột khác
        if (isset($validated['is_default']) && $validated['is_default']) {
            $team->kanbanColumns()->update(['is_default' => false]);
        }
        
        $column = $team->kanbanColumns()->create([
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#808080',
            'order' => $validated['order'] ?? $team->kanbanColumns()->count(),
            'is_default' => $validated['is_default'] ?? false,
            'created_by' => Auth::id()
        ]);
        
        return response()->json([
            'data' => $column
        ], 201);
    }
    
    /**
     * Cập nhật cột Kanban
     */
    public function update(Request $request, Team $team, KanbanColumn $column)
    {
        // Kiểm tra quyền truy cập và xác nhận column thuộc team
        if (!$team->members()->where('user_id', Auth::id())->where('role', 'admin')->exists() || 
            $column->team_id !== $team->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:50',
            'color' => 'nullable|string|max:7',
            'order' => 'sometimes|integer',
            'is_default' => 'sometimes|boolean'
        ]);
        
        // Nếu đánh dấu là cột mặc định, bỏ đánh dấu các cột khác
        if (isset($validated['is_default']) && $validated['is_default']) {
            $team->kanbanColumns()->where('id', '!=', $column->id)->update(['is_default' => false]);
        }
        
        $column->update($validated);
        
        return response()->json([
            'data' => $column
        ]);
    }
    
    /**
     * Xóa cột Kanban
     */
    public function destroy(Team $team, KanbanColumn $column)
    {
        // Kiểm tra quyền truy cập và xác nhận column thuộc team
        if (!$team->members()->where('user_id', Auth::id())->where('role', 'admin')->exists() || 
            $column->team_id !== $team->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Không cho phép xóa cột mặc định
        if ($column->is_default) {
            return response()->json(['message' => 'Cannot delete default column'], 422);
        }
        
        // Di chuyển tất cả task trong cột này sang cột mặc định
        $defaultColumn = $team->kanbanColumns()->where('is_default', true)->first();
        if ($defaultColumn) {
            $team->tasks()->where('column_id', $column->id)->update(['column_id' => $defaultColumn->id]);
        }
        
        $column->delete();
        
        return response()->json(null, 204);
    }
    
    /**
     * Cập nhật thứ tự các cột
     */
    public function updateOrder(Request $request, Team $team)
    {
        // Kiểm tra quyền truy cập
        if (!$team->members()->where('user_id', Auth::id())->where('role', 'admin')->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'columns' => 'required|array',
            'columns.*' => 'required|integer|exists:kanban_columns,id'
        ]);
        
        // Cập nhật thứ tự các cột
        foreach ($validated['columns'] as $index => $columnId) {
            $column = KanbanColumn::find($columnId);
            if ($column && $column->team_id === $team->id) {
                $column->update(['order' => $index]);
            }
        }
        
        return response()->json([
            'data' => $team->kanbanColumns()->orderBy('order')->get()
        ]);
    }
}