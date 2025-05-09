<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamRoleHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamRoleHistoryController extends Controller
{
    /**
     * Get role history for a team
     */
    public function index(Team $team)
    {
        // Verify user is a member of the team
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Get role history for the team with related user data
        $history = TeamRoleHistory::where('team_id', $team->id)
            ->with(['user', 'changedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($history);
    }

    /**
     * Get role history for a specific user in a team
     */
    public function userHistory(Team $team, User $user)
    {
        // Verify requester is a member of the team
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Verify target user is a member of the team
        if (!$team->members()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'User is not a member of this team'], 404);
        }

        // Get role history for the specific user in the team
        $history = TeamRoleHistory::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->with(['changedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($history);
    }
}
