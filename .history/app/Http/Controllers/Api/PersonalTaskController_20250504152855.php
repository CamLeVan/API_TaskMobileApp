<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PersonalTask;
use Illuminate\Http\Request;

class PersonalTaskController extends Controller
{
    public function index(Request $request)
    {
        $query = PersonalTask::where('user_id', $request->user()->id);

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

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
     * Filter tasks by status, priority, deadline
     */
    public function filter(Request $request)
    {
        $query = PersonalTask::where('user_id', $request->user()->id);

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

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

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
     * Search tasks by keyword
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2'
        ]);

        $keyword = $request->q;

        $tasks = PersonalTask::where('user_id', $request->user()->id)
            ->where(function($query) use ($keyword) {
                $query->where('title', 'like', "%{$keyword}%")
                      ->orWhere('description', 'like', "%{$keyword}%");
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($tasks);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'nullable|date',
            'priority' => 'nullable|integer|min:1|max:5',
            'status' => 'required|in:pending,in_progress,completed,overdue'
        ]);

        $task = PersonalTask::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
            'deadline' => $request->deadline,
            'priority' => $request->priority,
            'status' => $request->status
        ]);

        return response()->json($task, 201);
    }

    public function show(Request $request, PersonalTask $personalTask)
    {
        if ($personalTask->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($personalTask);
    }

    public function update(Request $request, PersonalTask $personalTask)
    {
        if ($personalTask->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'nullable|date',
            'priority' => 'nullable|integer|min:1|max:5',
            'status' => 'required|in:pending,in_progress,completed,overdue'
        ]);

        $personalTask->update($request->all());

        return response()->json($personalTask);
    }

    public function destroy(Request $request, PersonalTask $personalTask)
    {
        if ($personalTask->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $personalTask->delete();

        return response()->json(null, 204);
    }

    /**
     * Update task order and status (for Kanban board)
     */
    public function updateTaskOrder(Request $request)
    {
        $request->validate([
            'tasks' => 'required|array',
            'tasks.*.id' => 'required|exists:personal_tasks,id',
            'tasks.*.status' => 'required|in:pending,in_progress,completed,overdue',
            'tasks.*.order' => 'required|integer'
        ]);

        $user = $request->user();

        foreach ($request->tasks as $taskData) {
            $task = PersonalTask::find($taskData['id']);

            // Verify task belongs to this user
            if ($task && $task->user_id === $user->id) {
                $task->update([
                    'status' => $taskData['status'],
                    'order' => $taskData['order']
                ]);
            }
        }

        return response()->json(['message' => 'Task order updated']);
    }
}
