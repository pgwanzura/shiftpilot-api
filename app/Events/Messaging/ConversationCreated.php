<?php

namespace App\Events\Messaging;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationCreated implements ShouldBroadcast
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
        return 'conversation.created';
    }
}
