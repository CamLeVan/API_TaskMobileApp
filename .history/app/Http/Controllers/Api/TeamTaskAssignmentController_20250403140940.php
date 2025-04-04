<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamTask;
use App\Models\TeamTaskAssignment;
use App\Models\User;
use Illuminate\Http\Request;

class TeamTaskAssignmentController extends Controller
{
    public function index(Request $request, Team $team, TeamTask $task)
    {
        if (!$team->members()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($task->team_id !== $team->team_id) {
            return response()->json(['message' => 'Task not found in this team'], 404);
        }

        return response()->json($task->assignments()->with('assignedUser')->get());
    }

    public function store(Request $request, Team $team, TeamTask $task)
    {
        $member = $team->members()->where('user_id', $request->user()->id)->first();
        
        if (!$member || $member->role !== 'manager') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($task->team_id !== $team->team_id) {
            return response()->json(['message' => 'Task not found in this team'], 404);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|in:pending,in_progress,completed'
        ]);

        // Check if user is a team member
        if (!$team->members()->where('user_id', $request->user_id)->exists()) {
            return response()->json(['message' => 'User is not a member of this team'], 400);
        }

        // Check if user is already assigned
        if ($task->assignments()->where('assigned_to', $request->user_id)->exists()) {
            return response()->json(['message' => 'User is already assigned to this task'], 400);
        }

        $assignment = TeamTaskAssignment::create([
            'team_task_id' => $task->team_task_id,
            'assigned_to' => $request->user_id,
            'status' => $request->status
        ]);

        return response()->json($assignment->load('assignedUser'), 201);
    }

    public function update(Request $request, Team $team, TeamTask $task, TeamTaskAssignment $assignment)
    {
        if (!$team->members()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($task->team_id !== $team->team_id) {
            return response()->json(['message' => 'Task not found in this team'], 404);
        }

        if ($assignment->team_task_id !== $task->team_task_id) {
            return response()->json(['message' => 'Assignment not found for this task'], 404);
        }

        // Only manager or assigned user can update
        $member = $team->members()->where('user_id', $request->user()->id)->first();
        if (!$member || ($member->role !== 'manager' && $assignment->assigned_to !== $request->user()->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
            'progress' => 'required|integer|min:0|max:100'
        ]);

        $assignment->update($request->all());

        return response()->json($assignment->load('assignedUser'));
    }

    public function destroy(Request $request, Team $team, TeamTask $task, TeamTaskAssignment $assignment)
    {
        $member = $team->members()->where('user_id', $request->user()->id)->first();
        
        if (!$member || $member->role !== 'manager') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($task->team_id !== $team->team_id) {
            return response()->json(['message' => 'Task not found in this team'], 404);
        }

        if ($assignment->team_task_id !== $task->team_task_id) {
            return response()->json(['message' => 'Assignment not found for this task'], 404);
        }

        $assignment->delete();

        return response()->json(null, 204);
    }
}
