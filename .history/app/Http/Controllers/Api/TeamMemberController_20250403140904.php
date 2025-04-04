<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\Request;

class TeamMemberController extends Controller
{
    public function index(Request $request, Team $team)
    {
        if (!$team->members()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($team->members()->with('user')->get());
    }

    public function store(Request $request, Team $team)
    {
        $member = $team->members()->where('user_id', $request->user()->id)->first();
        
        if (!$member || $member->role !== 'manager') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:manager,member'
        ]);

        // Check if user is already a member
        if ($team->members()->where('user_id', $request->user_id)->exists()) {
            return response()->json(['message' => 'User is already a member of this team'], 400);
        }

        $teamMember = TeamMember::create([
            'team_id' => $team->team_id,
            'user_id' => $request->user_id,
            'role' => $request->role
        ]);

        return response()->json($teamMember->load('user'), 201);
    }

    public function destroy(Request $request, Team $team, User $user)
    {
        $member = $team->members()->where('user_id', $request->user()->id)->first();
        
        if (!$member || $member->role !== 'manager') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Prevent removing the last manager
        if ($team->members()->where('role', 'manager')->count() <= 1) {
            $targetMember = $team->members()->where('user_id', $user->id)->first();
            if ($targetMember && $targetMember->role === 'manager') {
                return response()->json(['message' => 'Cannot remove the last manager'], 400);
            }
        }

        $team->members()->where('user_id', $user->id)->delete();

        return response()->json(null, 204);
    }
}
