<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupChatMessage;
use App\Models\MessageReadStatus;
use App\Models\Team;
use Illuminate\Http\Request;

class GroupChatController extends Controller
{
    public function index(Request $request, Team $team)
    {
        if (!$team->members()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $messages = $team->chatMessages()
            ->with(['sender', 'readStatuses'])
            ->orderBy('timestamp', 'desc')
            ->paginate(50);

        return response()->json($messages);
    }

    public function store(Request $request, Team $team)
    {
        if (!$team->members()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'message' => 'required_without:file_url|string',
            'file_url' => 'required_without:message|url'
        ]);

        $message = GroupChatMessage::create([
            'team_id' => $team->id,
            'sender_id' => $request->user()->id,
            'message' => $request->message,
            'file_url' => $request->file_url
        ]);

        return response()->json($message->load(['sender', 'readStatuses']), 201);
    }

    public function markAsRead(Request $request, Team $team, GroupChatMessage $message)
    {
        if (!$team->members()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($message->team_id !== $team->id) {
            return response()->json(['message' => 'Message not found in this team'], 404);
        }

        // Check if already marked as read
        if ($message->readStatuses()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Message already marked as read'], 400);
        }

        MessageReadStatus::create([
            'message_id' => $message->message_id,
            'user_id' => $request->user()->id
        ]);

        return response()->json(['message' => 'Message marked as read']);
    }

    public function getUnreadCount(Request $request, Team $team)
    {
        if (!$team->members()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $unreadCount = $team->chatMessages()
            ->whereDoesntHave('readStatuses', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->count();

        return response()->json(['unread_count' => $unreadCount]);
    }
}
