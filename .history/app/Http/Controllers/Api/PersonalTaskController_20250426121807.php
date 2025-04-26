<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PersonalTask;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use App\Http\Resources\PersonalTaskResource;

class PersonalTaskController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $tasks = $request->user()->personalTasks()->paginate($perPage);
        
        return ApiResponse::fromPaginator($tasks, PersonalTaskResource::class);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'required|integer|min:1|max:5',
        ]);
        
        $task = $request->user()->personalTasks()->create($validated);
        
        return ApiResponse::success(
            new PersonalTaskResource($task),
            ['timestamp' => now()->timestamp],
            null,
            201
        );
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
}
