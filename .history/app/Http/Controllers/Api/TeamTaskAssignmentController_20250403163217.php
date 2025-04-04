<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamTask;
use App\Models\TeamTaskAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TeamTaskAssignmentController extends Controller
{
    public function index(Team $team, TeamTask $task)
    {
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($task->team_id !== $team->id) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        $assignments = $task->assignments()->with('assignedTo')->get();
        return response()->json($assignments);
    }

    public function store(Request $request, Team $team, TeamTask $task)
    {
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($task->team_id !== $team->id) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'assigned_to' => 'required|exists:users,id',
            'status' => 'required|in:pending,in_progress,completed',
            'progress' => 'required|integer|min:0|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $assignment = $task->assignments()->create([
            'assigned_to' => $request->assigned_to,
            'status' => $request->status,
            'progress' => $request->progress,
            'assigned_at' => now()
        ]);

        return response()->json($assignment->load('assignedTo'), 201);
    }

    public function update(Request $request, Team $team, TeamTask $task, TeamTaskAssignment $assignment)
    {
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($task->team_id !== $team->id) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        if ($assignment->team_task_id !== $task->id) {
            return response()->json(['message' => 'Assignment not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,in_progress,completed',
            'progress' => 'required|integer|min:0|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $assignment->update($request->all());
        return response()->json($assignment->load('assignedTo'));
    }

    public function destroy(Team $team, TeamTask $task, TeamTaskAssignment $assignment)
    {
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($task->team_id !== $team->id) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        if ($assignment->team_task_id !== $task->id) {
            return response()->json(['message' => 'Assignment not found'], 404);
        }

        $assignment->delete();
        return response()->json(null, 204);
    }
}
