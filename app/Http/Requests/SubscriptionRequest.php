<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'entity_type' => 'required|in:agency,employer',
            'entity_id' => 'required|integer',
            'plan_key' => 'required|string|max:255',
            'plan_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'interval' => 'nullable|in:monthly,yearly',
            'status' => 'nullable|in:active,past_due,cancelled',
            'started_at' => 'required|date',
            'current_period_start' => 'nullable|date',
            'current_period_end' => 'nullable|date|after:current_period_start',
            'meta' => 'nullable|array',
        ];
    }
}
