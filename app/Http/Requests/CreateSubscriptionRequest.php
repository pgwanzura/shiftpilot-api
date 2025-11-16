<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAgencyAdmin() && $this->user()->agency_id === $this->agency_id;
    }

    public function rules(): array
    {
        return [
            'agency_id' => 'required|exists:agencies,id',
            'plan_id' => 'required|exists:plans,id',
            'amount' => 'required|numeric|min:0',
            'interval' => 'sometimes|in:monthly,yearly',
        ];
    }
}
