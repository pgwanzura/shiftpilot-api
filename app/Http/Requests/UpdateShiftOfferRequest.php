<?php

namespace App\Http\Requests\ShiftOffer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShiftOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('offer'));
    }

    public function rules(): array
    {
        return [
            'expires_at' => 'sometimes|date|after:now',
            'response_notes' => 'nullable|string',
        ];
    }
}
