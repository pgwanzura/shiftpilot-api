<?php

namespace App\Http\Requests;

use \App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;

class CreateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Subscription::class);
    }

    public function rules(): array
    {
        return [
            'entity_type' => 'required|in:agency,employer',
            'entity_id' => 'required|integer',
            'plan_key' => 'required|string',
            'plan_name' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'interval' => 'required|in:monthly,yearly',
            'started_at' => 'required|date',
        ];
    }
}
