<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Requests\UpdateConversationParticipantRequest;
use App\Models\ConversationParticipant;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ConversationParticipantController extends Controller
{
    use AuthorizesRequests;

    public function update(UpdateConversationParticipantRequest $request, ConversationParticipant $participant): JsonResponse
    {
        $this->authorize('update', $participant);

        $participant->update($request->validated());

        return response()->json([
            'message' => 'Participant updated successfully',
            'participant' => $participant
        ]);
    }

    public function mute(ConversationParticipant $participant, Request $request): JsonResponse
    {
        $this->authorize('update', $participant);

        $hours = (int) $request->get('hours', 0);
        $participant->mute(now()->addHours($hours));

        return response()->json(['message' => 'Conversation muted successfully']);
    }

    public function unmute(ConversationParticipant $participant): JsonResponse
    {
        $this->authorize('update', $participant);

        $participant->unmute();

        return response()->json(['message' => 'Conversation unmuted successfully']);
    }

    public function destroy(ConversationParticipant $participant): JsonResponse
    {
        $this->authorize('delete', $participant);

        $participant->delete();

        return response()->json(['message' => 'Participant removed successfully']);
    }
}
