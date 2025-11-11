<?php

namespace App\Http\Requests;

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
            'agency_employee_id' => 'required|exists:agency_employees,id',
            'expires_at' => 'required|date|after:now'
        ];
    }
}
