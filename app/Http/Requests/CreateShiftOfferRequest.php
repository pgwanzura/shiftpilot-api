<?php

namespace App\Http\Requests\ShiftOffer;

use Illuminate\Foundation\Http\FormRequest;

class CreateShiftOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\ShiftOffer::class);
    }

    public function rules(): array
    {
        return [
            'shift_id' => 'required|exists:shifts,id',
            'employee_id' => 'required|exists:employees,id',
            'expires_at' => 'required|date|after:now',
        ];
    }
}
