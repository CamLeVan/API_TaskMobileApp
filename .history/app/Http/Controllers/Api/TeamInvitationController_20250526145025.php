<?php

namespace App\Http\Controllers\Api;

use App\Events\TeamInvitationAccepted;
use App\Events\TeamInvitationCancelled;
use App\Events\TeamInvitationCreated;
use App\Events\TeamInvitationRejected;
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

        return response()->json($invitations);
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

        // Create invitation with better error handling
        try {
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
        } catch (\Exception $e) {
            Log::error('Failed to create team invitation: ' . $e->getMessage());
            Log::error('Request data: ' . json_encode($request->all()));
            Log::error('Team ID: ' . $team->id);
            Log::error('User ID: ' . Auth::id());

            return response()->json([
                'message' => 'Failed to create invitation',
                'error' => $e->getMessage()
            ], 500);
        }

        // Kích hoạt sự kiện TeamInvitationCreated
        event(new TeamInvitationCreated($invitation));

        // TODO: Send email to user with invitation link

        return response()->json([
            'message' => 'Invitation sent successfully',
            'id' => $invitation->id,
            'team_id' => $team->id,
            'team_name' => $team->name,
            'email' => $invitation->email,
            'role' => $invitation->role,
            'status' => $invitation->status,
            'created_at' => $invitation->created_at,
            'expires_at' => $invitation->expires_at
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

        // Kiểm tra xem email của người dùng có khớp với email trong lời mời không
        if ($user->email !== $invitation->email) {
            return response()->json(['message' => 'This invitation was not sent to your email address'], 403);
        }

        // Kiểm tra xem người dùng đã là thành viên của nhóm chưa
        if ($team->members()->where('user_id', $user->id)->exists()) {
            $invitation->markAsRejected(); // Đánh dấu là đã từ chối vì người dùng đã là thành viên
            return response()->json(['message' => 'You are already a member of this team'], 400);
        }

        // Sử dụng transaction để đảm bảo tính toàn vẹn dữ liệu
        try {
            DB::beginTransaction();

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

            DB::commit();

            // Kích hoạt sự kiện TeamInvitationAccepted
            event(new TeamInvitationAccepted($invitation, $user));

            // Đồng bộ toàn bộ dữ liệu team cho user mới
            $teamData = $this->getFullTeamData($team, $user);

            return response()->json([
                'message' => 'Invitation accepted successfully',
                'team' => $teamData['team'],
                'role' => $invitation->role,
                'team_data' => $teamData
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to accept invitation: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to accept invitation'], 500);
        }
    }

    /**
     * Lấy toàn bộ dữ liệu team cho user mới join
     */
    private function getFullTeamData(Team $team, User $user)
    {
        // 1. Thông tin team cơ bản
        $teamInfo = [
            'id' => $team->id,
            'name' => $team->name,
            'description' => $team->description,
            'uuid' => $team->uuid,
            'created_at' => $team->created_at,
            'updated_at' => $team->updated_at
        ];

        // 2. Danh sách thành viên team
        $members = $team->members()->with('user')->get()->map(function ($member) {
            return [
                'id' => $member->user->id,
                'name' => $member->user->name,
                'email' => $member->user->email,
                'role' => $member->role,
                'joined_at' => $member->joined_at
            ];
        });

        // 3. Tin nhắn chat gần đây (50 tin nhắn cuối)
        $messages = $team->chatMessages()
            ->with(['sender', 'readStatuses'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->reverse()
            ->values()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'team_id' => $message->team_id,
                    'sender' => [
                        'id' => $message->sender->id,
                        'name' => $message->sender->name,
                        'email' => $message->sender->email
                    ],
                    'message' => $message->message,
                    'file_url' => $message->file_url,
                    'created_at' => $message->created_at,
                    'read_statuses' => $message->readStatuses->map(function ($status) {
                        return [
                            'user_id' => $status->user_id,
                            'read_at' => $status->read_at
                        ];
                    })
                ];
            });

        // 4. Team tasks (tất cả tasks của team)
        $teamTasks = $team->teamTasks()
            ->with(['assignments.assignedTo'])
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'team_id' => $task->team_id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'deadline' => $task->deadline,
                    'created_at' => $task->created_at,
                    'updated_at' => $task->updated_at,
                    'assignments' => $task->assignments->map(function ($assignment) {
                        return [
                            'user_id' => $assignment->assigned_to,
                            'user_name' => $assignment->assignedTo->name,
                            'assigned_at' => $assignment->assigned_at
                        ];
                    })
                ];
            });

        // 5. Documents metadata (không bao gồm file content)
        $documents = $team->documents()
            ->with(['folder'])
            ->get()
            ->map(function ($document) {
                return [
                    'id' => $document->id,
                    'team_id' => $document->team_id,
                    'name' => $document->name,
                    'file_path' => $document->file_path,
                    'file_size' => $document->file_size,
                    'file_type' => $document->file_type,
                    'folder_id' => $document->folder_id,
                    'folder_name' => $document->folder ? $document->folder->name : null,
                    'uploaded_by' => $document->uploaded_by,
                    'created_at' => $document->created_at,
                    'updated_at' => $document->updated_at
                ];
            });

        // 6. Document folders
        $folders = $team->documentFolders()
            ->get()
            ->map(function ($folder) {
                return [
                    'id' => $folder->id,
                    'team_id' => $folder->team_id,
                    'name' => $folder->name,
                    'parent_id' => $folder->parent_id,
                    'created_at' => $folder->created_at
                ];
            });

        return [
            'team' => $teamInfo,
            'members' => $members,
            'messages' => $messages,
            'team_tasks' => $teamTasks,
            'documents' => $documents,
            'folders' => $folders,
            'sync_time' => now()->toIso8601String()
        ];
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

        // Kiểm tra xem người dùng đã đăng nhập chưa
        $user = Auth::user();

        // Kiểm tra xem email của người dùng có khớp với email trong lời mời không
        if ($user->email !== $invitation->email) {
            return response()->json(['message' => 'This invitation was not sent to your email address'], 403);
        }

        try {
            // Đánh dấu lời mời là đã từ chối
            $invitation->markAsRejected();

            // Kích hoạt sự kiện TeamInvitationRejected
            event(new TeamInvitationRejected($invitation));

            return response()->json([
                'message' => 'Invitation rejected successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to reject invitation: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to reject invitation'], 500);
        }
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

        // Lưu ID trước khi xóa
        $invitationId = $invitation->id;
        $teamId = $invitation->team_id;

        // Xóa lời mời
        $invitation->delete();

        // Kích hoạt sự kiện TeamInvitationCancelled
        event(new TeamInvitationCancelled($invitationId, $teamId));

        return response()->json([
            'message' => 'Invitation cancelled successfully'
        ]);
    }

    /**
     * Get all invitations for the current user
     */
    public function getUserInvitations()
    {
        $user = Auth::user();

        // Lấy lời mời dựa trên email của người dùng
        $invitations = TeamInvitation::where('email', $user->email)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->with('team')
            ->get();

        // Trả về trực tiếp mảng lời mời, không bọc trong thuộc tính 'data'
        return response()->json($invitations);
    }

    /**
     * Invite a user to a team using UUID
     */
    public function inviteByUuid(Request $request, $teamUuid)
    {
        $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:manager,member'
        ]);

        // Tìm nhóm dựa trên UUID
        $team = Team::where('uuid', $teamUuid)->first();

        if (!$team) {
            return response()->json(['message' => 'Team not found'], 404);
        }

        // Verify user is a manager of the team
        $member = $team->members()->where('user_id', Auth::id())->first();
        if (!$member || $member->role !== 'manager') {
            return response()->json(['message' => 'Unauthorized - Only managers can send invitations'], 403);
        }

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

        // Kích hoạt sự kiện TeamInvitationCreated
        event(new TeamInvitationCreated($invitation));

        return response()->json([
            'message' => 'Invitation sent successfully',
            'id' => $invitation->id,
            'team_id' => $team->id,
            'team_uuid' => $team->uuid,
            'team_name' => $team->name,
            'email' => $invitation->email,
            'role' => $invitation->role,
            'status' => $invitation->status,
            'created_at' => $invitation->created_at,
            'expires_at' => $invitation->expires_at
        ], 201);
    }
}
