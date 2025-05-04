<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamTask;
use Carbon\Carbon;
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

    /**
     * Filter team tasks by status, priority, deadline, assigned user
     */
    public function filter(Request $request, Team $team)
    {
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = $team->tasks();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by deadline
        if ($request->has('deadline')) {
            switch ($request->deadline) {
                case 'today':
                    $query->whereDate('deadline', Carbon::today());
                    break;
                case 'tomorrow':
                    $query->whereDate('deadline', Carbon::tomorrow());
                    break;
                case 'this_week':
                    $query->whereBetween('deadline', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    break;
                case 'next_week':
                    $query->whereBetween('deadline', [
                        Carbon::now()->addWeek()->startOfWeek(),
                        Carbon::now()->addWeek()->endOfWeek()
                    ]);
                    break;
                case 'overdue':
                    $query->where('deadline', '<', Carbon::today())
                          ->where('status', '!=', 'completed');
                    break;
                case 'upcoming':
                    $query->where('deadline', '>=', Carbon::today());
                    break;
            }
        }

        // Filter by assigned user
        if ($request->has('assigned_to')) {
            $query->whereHas('assignments', function($q) use ($request) {
                $q->where('user_id', $request->assigned_to);
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Load relationships
        $query->with(['creator', 'assignments.assignedTo']);

        // Apply pagination if requested
        $perPage = $request->get('per_page');
        if ($perPage) {
            $tasks = $query->paginate($perPage);
            return response()->json($tasks);
        }

        $tasks = $query->get();
        return response()->json($tasks);
    }

    /**
     * Search team tasks by keyword
     */
    public function search(Request $request, Team $team)
    {
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'q' => 'required|string|min:2'
        ]);

        $keyword = $request->q;

        $tasks = $team->tasks()
            ->where(function($query) use ($keyword) {
                $query->where('title', 'like', "%{$keyword}%")
                      ->orWhere('description', 'like', "%{$keyword}%");
            })
            ->with(['creator', 'assignments.assignedTo'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($tasks);
    }
}
