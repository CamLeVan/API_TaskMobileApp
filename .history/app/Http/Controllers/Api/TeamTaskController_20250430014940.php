<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TeamTaskController extends Controller
{
    public function index(Team $team)
    {
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tasks = $team->tasks()->with(['creator', 'assignments.assignedTo'])->get();

        return response()->json($tasks);
    }

    public function store(Request $request, Team $team)
    {
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'nullable|date',
            'priority' => 'required|integer|min:1|max:5',
            'status' => 'required|in:pending,in_progress,completed,overdue'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $task = $team->tasks()->create([
            'title' => $request->title,
            'description' => $request->description,
            'deadline' => $request->deadline,
            'priority' => $request->priority,
            'status' => $request->status,
            'created_by' => Auth::id()
        ]);

        return response()->json($task, 201);
    }

    public function show(Team $team, TeamTask $task)
    {
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($task->team_id !== $team->id) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        $task->load(['creator', 'assignments.assignedTo']);
        return response()->json($task);
    }

    public function update(Request $request, Team $team, TeamTask $task)
    {
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($task->team_id !== $team->id) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'nullable|date',
            'priority' => 'sometimes|required|integer|min:1|max:5',
            'status' => 'sometimes|required|in:pending,in_progress,completed,overdue'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $task->update($request->all());
        return response()->json($task);
    }

    public function destroy(Team $team, TeamTask $task)
    {
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($task->team_id !== $team->id) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        $task->delete();
        return response()->json(null, 204);
    }

    /**
     * Update task order and status (for Kanban board)
     */
    public function updateTaskOrder(Request $request, Team $team)
    {
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'tasks' => 'required|array',
            'tasks.*.id' => 'required|exists:team_tasks,id',
            'tasks.*.status' => 'required|in:pending,in_progress,completed,overdue',
            'tasks.*.order' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        foreach ($request->tasks as $taskData) {
            $task = TeamTask::find($taskData['id']);

            // Verify task belongs to this team
            if ($task && $task->team_id === $team->id) {
                $task->update([
                    'status' => $taskData['status'],
                    'order' => $taskData['order']
                ]);
            }
        }

        return response()->json(['message' => 'Task order updated']);
    }
}
