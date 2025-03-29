<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamTask;
use Illuminate\Http\Request;

class TeamTaskController extends Controller
{
    public function index(Request $request, Team $team)
    {
        if (!$team->members()->where('user_id', $request->user()->user_id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($team->tasks()->with(['creator', 'assignments'])->get());
    }

    public function store(Request $request, Team $team)
    {
        $member = $team->members()->where('user_id', $request->user()->user_id)->first();
        
        if (!$member || $member->role !== 'manager') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'nullable|date',
            'priority' => 'nullable|integer|min:1|max:5',
            'status' => 'required|in:pending,in_progress,completed,overdue'
        ]);

        $task = TeamTask::create([
            'team_id' => $team->team_id,
            'created_by' => $request->user()->user_id,
            'title' => $request->title,
            'description' => $request->description,
            'deadline' => $request->deadline,
            'priority' => $request->priority,
            'status' => $request->status
        ]);

        return response()->json($task->load(['creator', 'assignments']), 201);
    }

    public function show(Request $request, Team $team, TeamTask $task)
    {
        if (!$team->members()->where('user_id', $request->user()->user_id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($task->team_id !== $team->team_id) {
            return response()->json(['message' => 'Task not found in this team'], 404);
        }

        return response()->json($task->load(['creator', 'assignments']));
    }

    public function update(Request $request, Team $team, TeamTask $task)
    {
        $member = $team->members()->where('user_id', $request->user()->user_id)->first();
        
        if (!$member || $member->role !== 'manager') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($task->team_id !== $team->team_id) {
            return response()->json(['message' => 'Task not found in this team'], 404);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'nullable|date',
            'priority' => 'nullable|integer|min:1|max:5',
            'status' => 'required|in:pending,in_progress,completed,overdue'
        ]);

        $task->update($request->all());

        return response()->json($task->load(['creator', 'assignments']));
    }

    public function destroy(Request $request, Team $team, TeamTask $task)
    {
        $member = $team->members()->where('user_id', $request->user()->user_id)->first();
        
        if (!$member || $member->role !== 'manager') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($task->team_id !== $team->team_id) {
            return response()->json(['message' => 'Task not found in this team'], 404);
        }

        $task->delete();

        return response()->json(null, 204);
    }
}
