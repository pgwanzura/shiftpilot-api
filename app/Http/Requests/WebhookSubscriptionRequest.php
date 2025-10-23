<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebhookSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAgencyAdmin() || $this->user()->isEmployerAdmin() || $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'owner_type' => 'required|in:agency,employer',
            'owner_id' => 'required|integer',
            'url' => 'required|url',
            'events' => 'required|array',
            'secret' => 'required|string',
            'status' => 'nullable|in:active,inactive',
        ];
    }
}
