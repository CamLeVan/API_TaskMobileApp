<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TeamMember;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TeamInvitationController extends Controller
{
    /**
     * Get all invitations for a team
     */
    public function index(Team $team)
    {
        // Verify user is a manager of the team
        $member = $team->members()->where('user_id', Auth::id())->first();
        if (!$member || $member->role !== 'manager') {
            return response()->json(['message' => 'Unauthorized - Only managers can view invitations'], 403);
        }

        // Lấy danh sách lời mời của nhóm
        $invitations = $team->invitations()
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->get();

        return response()->json([
            'data' => $invitations
        ]);
    }

    /**
     * Create a new invitation
     */
    public function store(Request $request, Team $team)
    {
        // Verify user is a manager of the team
        $member = $team->members()->where('user_id', Auth::id())->first();
        if (!$member || $member->role !== 'manager') {
            return response()->json(['message' => 'Unauthorized - Only managers can send invitations'], 403);
        }

        $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:manager,member'
        ]);

        // Check if user already exists
        $user = User::where('email', $request->email)->first();

        // Check if user is already a member
        if ($user && $team->members()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'User is already a member of this team'], 400);
        }

        // Check if there's already a pending invitation for this email
        $existingInvitation = $team->invitations()
            ->where('email', $request->email)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingInvitation) {
            return response()->json(['message' => 'An invitation has already been sent to this email'], 400);
        }

        // Generate invitation token
        $token = Str::random(64);

        // Create invitation
        $invitation = new TeamInvitation([
            'team_id' => $team->id,
            'created_by' => Auth::id(),
            'email' => $request->email,
            'role' => $request->role,
            'token' => $token,
            'status' => 'pending',
            'expires_at' => Carbon::now()->addDays(7)
        ]);

        $invitation->save();

        // TODO: Send email to user with invitation link

        return response()->json([
            'message' => 'Invitation sent successfully',
            'data' => [
                'id' => $invitation->id,
                'team_id' => $team->id,
                'team_name' => $team->name,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'status' => $invitation->status,
                'created_at' => $invitation->created_at,
                'expires_at' => $invitation->expires_at
            ]
        ], 201);
    }

    /**
     * Accept an invitation
     */
    public function accept(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        // Tìm lời mời dựa trên token
        $invitation = TeamInvitation::where('token', $request->token)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if (!$invitation) {
            return response()->json(['message' => 'Invalid or expired invitation'], 404);
        }

        // Lấy thông tin nhóm
        $team = $invitation->team;

        // Kiểm tra xem người dùng đã đăng nhập chưa
        $user = Auth::user();

        // Kiểm tra xem người dùng đã là thành viên của nhóm chưa
        if ($team->members()->where('user_id', $user->id)->exists()) {
            $invitation->markAsRejected(); // Đánh dấu là đã từ chối vì người dùng đã là thành viên
            return response()->json(['message' => 'You are already a member of this team'], 400);
        }

        // Thêm người dùng vào nhóm với vai trò được chỉ định
        $teamMember = new TeamMember([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => $invitation->role,
            'joined_at' => now()
        ]);

        $teamMember->save();

        // Đánh dấu lời mời là đã chấp nhận
        $invitation->markAsAccepted();

        return response()->json([
            'message' => 'Invitation accepted successfully',
            'data' => [
                'team' => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'description' => $team->description,
                    'created_at' => $team->created_at
                ],
                'role' => $invitation->role
            ]
        ]);
    }

    /**
     * Reject an invitation
     */
    public function reject(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        // Tìm lời mời dựa trên token
        $invitation = TeamInvitation::where('token', $request->token)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if (!$invitation) {
            return response()->json(['message' => 'Invalid or expired invitation'], 404);
        }

        // Đánh dấu lời mời là đã từ chối
        $invitation->markAsRejected();

        return response()->json([
            'message' => 'Invitation rejected successfully'
        ]);
    }

    /**
     * Cancel an invitation (by a team manager)
     */
    public function destroy(Team $team, $invitationId)
    {
        // Verify user is a manager of the team
        $member = $team->members()->where('user_id', Auth::id())->first();
        if (!$member || $member->role !== 'manager') {
            return response()->json(['message' => 'Unauthorized - Only managers can cancel invitations'], 403);
        }

        // Tìm lời mời
        $invitation = $team->invitations()->find($invitationId);

        if (!$invitation) {
            return response()->json(['message' => 'Invitation not found'], 404);
        }

        // Xóa lời mời
        $invitation->delete();

        return response()->json([
            'message' => 'Invitation cancelled successfully'
        ]);
    }
}
