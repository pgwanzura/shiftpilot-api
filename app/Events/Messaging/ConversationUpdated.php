<?php

namespace App\Events\Messaging;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation
    ) {}

    public function broadcastOn(): array
    {
        $channels = [];
        foreach ($this->conversation->participants as $participant) {
            $channels[] = new PrivateChannel("user.{$participant->user_id}");
        }
        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'conversation.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation' => [
                'id' => $this->conversation->id,
                'title' => $this->conversation->title,
                'conversation_type' => $this->conversation->conversation_type,
                'last_message_at' => $this->conversation->last_message_at,
                'last_message_id' => $this->conversation->last_message_id,
            ],
            'unread_count' => $this->conversation->participants()
                ->where('user_id', '!=', $this->conversation->lastMessage->sender_id ?? null)
                ->count(),
        ];
    }
}
