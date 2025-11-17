<?php

namespace App\Http\Controllers;

use App\Http\Requests\Messaging\Conversation\CreateConversationRequest;
use App\Http\Requests\Messaging\Message\SendMessageRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Services\MessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ConversationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private MessagingService $messagingService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $conversations = $this->messagingService->getUserConversations($request->user()->id, $request->all());
        return ConversationResource::collection($conversations);
    }

    public function store(CreateConversationRequest $request): ConversationResource
    {
        $conversation = $this->messagingService->createConversation($request->validated(), $request->user()->id);
        return new ConversationResource($conversation);
    }

    public function show(int $conversationId, Request $request): ConversationResource
    {
        $conversation = Conversation::with(['participants.user', 'lastMessage.sender'])->findOrFail($conversationId);
        $this->authorize('view', $conversation);

        return new ConversationResource($conversation);
    }

    public function getMessages(int $conversationId, Request $request): AnonymousResourceCollection
    {
        $conversation = Conversation::findOrFail($conversationId);
        $this->authorize('view', $conversation);

        $messages = $this->messagingService->getConversationMessages($conversationId, $request->user()->id, $request->all());
        return MessageResource::collection($messages);
    }

    public function sendMessage(SendMessageRequest $request): MessageResource
    {
        $message = $this->messagingService->sendMessage($request->validated(), $request->user()->id);
        return new MessageResource($message);
    }

    public function markAsRead(int $conversationId, Request $request): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $this->authorize('view', $conversation);

        $this->messagingService->markConversationAsRead($conversationId, $request->user()->id);

        return response()->json(['message' => 'Conversation marked as read']);
    }

    public function addParticipant(int $conversationId, Request $request): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        if (!$conversation->participants()->where('user_id', $request->user()->id)->where('role', 'admin')->exists()) {
            abort(403, 'Unauthorized to add participants');
        }
        
        $this->authorize('addParticipant', $conversation);

        $participant = $this->messagingService->addParticipant($conversationId, $request->user_id, $request->user()->id);

        return response()->json([
            'message' => 'Participant added successfully',
            'participant' => $participant
        ]);
    }

    public function removeParticipant(int $conversationId, int $userId, Request $request): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $this->authorize('removeParticipant', $conversation);

        $this->messagingService->removeParticipant($conversationId, $userId);

        return response()->json(['message' => 'Participant removed successfully']);
    }

    public function leaveConversation(int $conversationId, Request $request): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $this->authorize('view', $conversation);

        $this->messagingService->removeParticipant($conversationId, $request->user()->id);

        return response()->json(['message' => 'You have left the conversation']);
    }

    public function getUnreadCount(Request $request): JsonResponse
    {
        $count = $this->messagingService->getUnreadCount($request->user()->id);
        return response()->json(['unread_count' => $count]);
    }
}
