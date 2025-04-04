<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeamMemberController extends Controller
{
    public function index(Team $team)
    {
        if (!$team->members()->where('user_id', auth()->id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $members = $team->members()->with('user')->get();
        return response()->json($members);
    }

    public function store(Request $request, Team $team)
    {
        if (!$team->members()->where('user_id', auth()->id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:manager,member'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($team->members()->where('user_id', $request->user_id)->exists()) {
            return response()->json(['message' => 'User is already a member of this team'], 400);
        }

        $member = $team->members()->create([
            'user_id' => $request->user_id,
            'role' => $request->role,
            'joined_at' => now()
        ]);

        return response()->json($member->load('user'), 201);
    }

    public function destroy(Team $team, TeamMember $member)
    {
        if (!$team->members()->where('user_id', auth()->id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($member->team_id !== $team->id) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        if ($member->role === 'manager' && $team->members()->where('role', 'manager')->count() <= 1) {
            return response()->json(['message' => 'Cannot remove the last manager'], 400);
        }

        $member->delete();
        return response()->json(null, 204);
    }
}

