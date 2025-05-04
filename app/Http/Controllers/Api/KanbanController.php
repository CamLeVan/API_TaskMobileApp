<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KanbanController extends Controller
{
    /**
     * Get Kanban board data for a team
     */
    public function getTeamKanban(Request $request, Team $team)
    {
        // Verify user is a member of the team
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Get all tasks for the team
        $tasks = TeamTask::where('team_id', $team->id)
            ->with(['creator', 'assignments.assignedTo'])
            ->orderBy('order')
            ->get();
            
        // Group tasks by status
        $columns = [
            'backlog' => [],
            'pending' => [],
            'in_progress' => [],
            'review' => [],
            'completed' => []
        ];
        
        foreach ($tasks as $task) {
            $status = $task->status;
            
            // Map status to column
            $column = match ($status) {
                'pending' => 'pending',
                'in_progress' => 'in_progress',
                'completed' => 'completed',
                'review' => 'review',
                default => 'backlog'
            };
            
            $columns[$column][] = [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'priority' => $task->priority,
                'deadline' => $task->deadline ? $task->deadline->toIso8601String() : null,
                'created_at' => $task->created_at->toIso8601String(),
                'creator' => [
                    'id' => $task->creator->id,
                    'name' => $task->creator->name
                ],
                'assignees' => $task->assignments->map(function($assignment) {
                    return [
                        'id' => $assignment->assignedTo->id,
                        'name' => $assignment->assignedTo->name
                    ];
                })
            ];
        }
        
        return response()->json([
            'team_id' => $team->id,
            'team_name' => $team->name,
            'columns' => $columns
        ]);
    }
    
    /**
     * Update task status (move between columns)
     */
    public function moveTask(Request $request, Team $team, TeamTask $task)
    {
        // Verify user is a member of the team
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Verify task belongs to the team
        if ($task->team_id !== $team->id) {
            return response()->json(['message' => 'Task not found'], 404);
        }
        
        $request->validate([
            'status' => 'required|in:backlog,pending,in_progress,review,completed',
            'order' => 'required|integer|min:0'
        ]);
        
        // Update task status and order
        $task->status = $request->status;
        $task->order = $request->order;
        $task->save();
        
        // Reorder other tasks in the same column
        TeamTask::where('team_id', $team->id)
            ->where('status', $request->status)
            ->where('id', '!=', $task->id)
            ->where('order', '>=', $request->order)
            ->increment('order');
            
        return response()->json([
            'message' => 'Task moved successfully',
            'task' => $task->fresh()
        ]);
    }
    
    /**
     * Update column order for multiple tasks
     */
    public function updateColumnOrder(Request $request, Team $team)
    {
        // Verify user is a member of the team
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'column' => 'required|in:backlog,pending,in_progress,review,completed',
            'task_ids' => 'required|array',
            'task_ids.*' => 'integer|exists:team_tasks,id'
        ]);
        
        $column = $request->column;
        $taskIds = $request->task_ids;
        
        // Verify all tasks belong to the team
        $count = TeamTask::where('team_id', $team->id)
            ->whereIn('id', $taskIds)
            ->count();
            
        if ($count !== count($taskIds)) {
            return response()->json(['message' => 'Some tasks do not belong to this team'], 400);
        }
        
        // Update task order
        foreach ($taskIds as $index => $taskId) {
            TeamTask::where('id', $taskId)
                ->update([
                    'status' => $column,
                    'order' => $index
                ]);
        }
        
        return response()->json([
            'message' => 'Column order updated successfully'
        ]);
    }
}
