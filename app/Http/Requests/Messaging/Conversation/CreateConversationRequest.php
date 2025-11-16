<?php

namespace App\Http\Requests\Messaging\Conversation;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Conversation;

class CreateConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Conversation::class);
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'conversation_type' => 'required|in:direct,group,shift,assignment',
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'exists:users,id',
            'context_type' => 'sometimes|string',
            'context_id' => 'sometimes|integer',
        ];
    }
}
