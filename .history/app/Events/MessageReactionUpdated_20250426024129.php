<?php

namespace App\Events;

use App\Models\GroupChatMessage;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReactionUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public GroupChatMessage $message;
    public User $user;
    public string $reaction;
    public string $action;

    /**
     * Create a new event instance.
     */
    public function __construct(GroupChatMessage $message, User $user, string $reaction, string $action)
    {
        $this->message = $message;
        $this->user = $user;
        $this->reaction = $reaction;
        $this->action = $action; // 'added' or 'removed'
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('teams.' . $this->message->team_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message-reaction-updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->message->id,
            'team_id' => $this->message->team_id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'reaction' => $this->reaction,
            'action' => $this->action,
            'timestamp' => now()->toIso8601String(),
        ];
    }
} 