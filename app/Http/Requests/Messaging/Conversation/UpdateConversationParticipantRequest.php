<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConversationParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('participant'));
    }

    public function rules(): array
    {
        return [
            'role' => 'sometimes|in:participant,admin',
            'muted_until' => 'sometimes|date|after:now',
        ];
    }
}
