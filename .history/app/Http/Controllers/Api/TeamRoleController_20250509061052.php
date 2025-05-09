<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\TeamRoleHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TeamRoleController extends Controller
{
    /**
     * Get all roles for a team
     */
    public function index(Team $team)
    {
        // Verify user is a member of the team
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Get all roles with counts
        $roles = TeamMember::where('team_id', $team->id)
            ->select('role', \DB::raw('count(*) as count'))
            ->groupBy('role')
            ->get();

        return response()->json($roles);
    }

    /**
     * Create a new role for a team
     */
    public function store(Request $request, Team $team)
    {
        // Verify user is a manager of the team
        $member = $team->members()->where('user_id', Auth::id())->first();
        if (!$member || $member->role !== 'manager') {
            return response()->json(['message' => 'Unauthorized - Only managers can create roles'], 403);
        }

        $request->validate([
            'role_name' => 'required|string|max:50',
            'permissions' => 'required|array',
            'permissions.*' => 'in:view,create,update,delete,manage_members'
        ]);

        // In a real implementation, you would store this in a roles table
        // For this example, we'll just return a success message

        return response()->json([
            'message' => 'Role created successfully',
            'role' => [
                'name' => $request->role_name,
                'permissions' => $request->permissions
            ]
        ], 201);
    }

    /**
     * Update a user's role in a team
     */
    public function updateMemberRole(Request $request, Team $team, User $user)
    {
        // Verify requester is a manager of the team
        $requesterMember = $team->members()->where('user_id', Auth::id())->first();
        if (!$requesterMember || $requesterMember->role !== 'manager') {
            return response()->json(['message' => 'Unauthorized - Only managers can update roles'], 403);
        }

        // Verify target user is a member of the team
        $targetMember = $team->members()->where('user_id', $user->id)->first();
        if (!$targetMember) {
            return response()->json(['message' => 'User is not a member of this team'], 404);
        }

        $request->validate([
            'role' => 'required|string|in:manager,member,custom_role',
            'reason' => 'nullable|string|max:255'
        ]);

        // Prevent removing the last manager
        if ($targetMember->role === 'manager' && $request->role !== 'manager') {
            $managerCount = $team->members()->where('role', 'manager')->count();
            if ($managerCount <= 1) {
                return response()->json(['message' => 'Cannot remove the last manager'], 400);
            }
        }

        // Store the old role before updating
        $oldRole = $targetMember->role;

        // Update the member's role
        $targetMember->role = $request->role;
        $targetMember->save();

        // Record the role change in history
        TeamRoleHistory::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'changed_by' => Auth::id(),
            'old_role' => $oldRole,
            'new_role' => $request->role,
            'reason' => $request->reason
        ]);

        return response()->json([
            'message' => 'Role updated successfully',
            'member' => $targetMember->load('user')
        ]);
    }

    /**
     * Update a user's permissions in a team
     */
    public function updateMemberPermissions(Request $request, Team $team, User $user)
    {
        // Verify requester is a manager of the team
        $requesterMember = $team->members()->where('user_id', Auth::id())->first();
        if (!$requesterMember || $requesterMember->role !== 'manager') {
            return response()->json(['message' => 'Unauthorized - Only managers can update permissions'], 403);
        }

        // Verify target user is a member of the team
        $targetMember = $team->members()->where('user_id', $user->id)->first();
        if (!$targetMember) {
            return response()->json(['message' => 'User is not a member of this team'], 404);
        }

        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'in:view,create,update,delete,manage_members'
        ]);

        // In a real implementation, you would store permissions in a database
        // For this example, we'll just return a success message

        return response()->json([
            'message' => 'Permissions updated successfully',
            'user_id' => $user->id,
            'permissions' => $request->permissions
        ]);
    }
}
