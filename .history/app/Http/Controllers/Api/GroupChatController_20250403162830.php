<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupChatMessage;
use App\Models\MessageReadStatus;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GroupChatController extends Controller
{
    public function index(Team $team)
    {
        if (!$team->members()->where('user_id', auth()->id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $messages = $team->chatMessages()
            ->with(['sender', 'readStatuses.user'])
            ->orderBy('timestamp', 'desc')
            ->paginate(50);

        return response()->json($messages);
    }

    public function store(Request $request, Team $team)
    {
        if (!$team->members()->where('user_id', auth()->id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
            'file_url' => 'nullable|url'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $message = $team->chatMessages()->create([
            'sender_id' => auth()->id(),
            'message' => $request->message,
            'file_url' => $request->file_url,
            'timestamp' => now()
        ]);

        return response()->json($message->load(['sender', 'readStatuses.user']), 201);
    }

    public function destroy(Team $team, GroupChatMessage $message)
    {
        if (!$team->members()->where('user_id', auth()->id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($message->team_id !== $team->id) {
            return response()->json(['message' => 'Message not found'], 404);
        }

        if ($message->sender_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $message->delete();
        return response()->json(null, 204);
    }

    public function markAsRead(Team $team, GroupChatMessage $message)
    {
        if (!$team->members()->where('user_id', auth()->id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($message->team_id !== $team->id) {
            return response()->json(['message' => 'Message not found'], 404);
        }

        $message->readStatuses()->updateOrCreate(
            ['user_id' => auth()->id()],
            ['read_at' => now()]
        );

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
