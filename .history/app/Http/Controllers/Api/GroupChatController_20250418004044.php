<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\NewChatMessage;
use App\Events\UserTyping;
use App\Models\GroupChatMessage;
use App\Models\MessageReadStatus;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GroupChatController extends Controller
{
    public function index(Request $request, Team $team)
    {
        if (!$team->members()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $page = $request->get('page', 1);
        $cacheKey = "team.{$team->id}.messages.page.{$page}";

        $messages = Cache::remember($cacheKey, 60, function () use ($team) {
            return $team->chatMessages()
                ->with(['sender', 'readStatuses'])
                ->orderBy('created_at', 'desc')
                ->paginate(50);
        });

        return response()->json($messages);
    }

    public function store(Request $request, Team $team)
    {
        if (!$team->members()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'message' => 'required_without:file_url|string',
            'file_url' => 'required_without:message|url',
            'client_temp_id' => 'required|string' // For message tracking
        ]);

        try {
            DB::beginTransaction();

            $message = GroupChatMessage::create([
                'team_id' => $team->id,
                'sender_id' => $request->user()->id,
                'message' => $request->message,
                'file_url' => $request->file_url,
                'status' => GroupChatMessage::STATUS_SENT,
                'client_temp_id' => $request->client_temp_id
            ]);

            $message->load('sender');

            // Clear cache for this team's messages
            Cache::tags(["team.{$team->id}.messages"])->flush();

            broadcast(new NewChatMessage($message))->toOthers();

            DB::commit();

            return response()->json([
                'message' => $message,
                'status' => 'success',
                'client_temp_id' => $request->client_temp_id
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to send message',
                'client_temp_id' => $request->client_temp_id,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function markAsRead(Request $request, Team $team, GroupChatMessage $message)
    {
        if (!$team->members()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($message->team_id !== $team->id) {
            return response()->json(['message' => 'Message not found in this team'], 404);
        }

        try {
            DB::beginTransaction();

            $readStatus = MessageReadStatus::firstOrCreate([
                'message_id' => $message->id,
                'user_id' => $request->user()->id
            ]);

            // Broadcast message read status to other users
            broadcast(new MessageRead($message, $request->user()))->toOthers();

            DB::commit();

            return response()->json(['message' => 'Message marked as read']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to mark message as read'], 500);
        }
    }

    public function getUnreadCount(Request $request, Team $team)
    {
        if (!$team->members()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $cacheKey = "team.{$team->id}.unread.{$request->user()->id}";

        $unreadCount = Cache::remember($cacheKey, 30, function () use ($team, $request) {
            return $team->chatMessages()
                ->whereDoesntHave('readStatuses', function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id);
                })
                ->count();
        });

        return response()->json(['unread_count' => $unreadCount]);
    }

    public function updateTypingStatus(Request $request, Team $team)
    {
        if (!$team->members()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'is_typing' => 'required|boolean'
        ]);

        broadcast(new UserTyping($request->user(), $team, $request->is_typing))->toOthers();

        return response()->json(['status' => 'success']);
    }

    public function retry(Request $request, Team $team, string $clientTempId)
    {
        if (!$team->members()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $message = GroupChatMessage::where('client_temp_id', $clientTempId)
            ->where('sender_id', $request->user()->id)
            ->first();

        if (!$message) {
            return response()->json(['message' => 'Message not found'], 404);
        }

        try {
            $message->status = GroupChatMessage::STATUS_SENT;
            $message->save();

            broadcast(new NewChatMessage($message))->toOthers();

            return response()->json([
                'message' => $message,
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retry sending message',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
