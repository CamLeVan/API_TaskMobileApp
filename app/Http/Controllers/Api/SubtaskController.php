<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PersonalTask;
use App\Models\Subtask;
use App\Models\TeamTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubtaskController extends Controller
{
    /**
     * Get all subtasks for a task
     */
    public function index(Request $request, $taskType, $taskId)
    {
        $task = $this->getTask($taskType, $taskId);
        
        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }
        
        if (!$this->authorizeTask($task)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $subtasks = $task->subtasks()->orderBy('order')->get();
        
        return response()->json($subtasks);
    }
    
    /**
     * Create a new subtask
     */
    public function store(Request $request, $taskType, $taskId)
    {
        $task = $this->getTask($taskType, $taskId);
        
        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }
        
        if (!$this->authorizeTask($task)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'title' => 'required|string|max:255',
            'completed' => 'boolean',
            'order' => 'integer'
        ]);
        
        $subtask = $task->subtasks()->create([
            'title' => $request->title,
            'completed' => $request->completed ?? false,
            'order' => $request->order ?? ($task->subtasks()->max('order') + 1)
        ]);
        
        return response()->json($subtask, 201);
    }
    
    /**
     * Update a subtask
     */
    public function update(Request $request, $taskType, $taskId, Subtask $subtask)
    {
        $task = $this->getTask($taskType, $taskId);
        
        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }
        
        if (!$this->authorizeTask($task)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Verify subtask belongs to this task
        if ($subtask->taskable_id != $task->id || $subtask->taskable_type != get_class($task)) {
            return response()->json(['message' => 'Subtask not found for this task'], 404);
        }
        
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'completed' => 'sometimes|boolean',
            'order' => 'sometimes|integer'
        ]);
        
        $subtask->update($request->only(['title', 'completed', 'order']));
        
        return response()->json($subtask);
    }
    
    /**
     * Delete a subtask
     */
    public function destroy($taskType, $taskId, Subtask $subtask)
    {
        $task = $this->getTask($taskType, $taskId);
        
        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }
        
        if (!$this->authorizeTask($task)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Verify subtask belongs to this task
        if ($subtask->taskable_id != $task->id || $subtask->taskable_type != get_class($task)) {
            return response()->json(['message' => 'Subtask not found for this task'], 404);
        }
        
        $subtask->delete();
        
        return response()->json(null, 204);
    }
    
    /**
     * Update order of multiple subtasks
     */
    public function updateOrder(Request $request, $taskType, $taskId)
    {
        $task = $this->getTask($taskType, $taskId);
        
        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }
        
        if (!$this->authorizeTask($task)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'subtasks' => 'required|array',
            'subtasks.*.id' => 'required|exists:subtasks,id',
            'subtasks.*.order' => 'required|integer'
        ]);
        
        foreach ($request->subtasks as $item) {
            $subtask = Subtask::find($item['id']);
            
            // Verify subtask belongs to this task
            if ($subtask && $subtask->taskable_id == $task->id && $subtask->taskable_type == get_class($task)) {
                $subtask->update(['order' => $item['order']]);
            }
        }
        
        return response()->json(['message' => 'Subtask order updated']);
    }
    
    /**
     * Helper to get task by type and ID
     */
    private function getTask($taskType, $taskId)
    {
        if ($taskType === 'personal') {
            return PersonalTask::find($taskId);
        } elseif ($taskType === 'team') {
            return TeamTask::find($taskId);
        }
        
        return null;
    }
    
    /**
     * Helper to check if user is authorized to access the task
     */
    private function authorizeTask($task)
    {
        $user = Auth::user();
        
        if ($task instanceof PersonalTask) {
            return $task->user_id === $user->id;
        } elseif ($task instanceof TeamTask) {
            return $task->team->members()->where('user_id', $user->id)->exists();
        }
        
        return false;
    }
}
