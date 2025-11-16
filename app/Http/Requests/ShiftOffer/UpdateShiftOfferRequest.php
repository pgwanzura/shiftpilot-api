<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Shift;

class CreateShiftOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\ShiftOffer::class);
    }

    public function rules(): array
    {
        return [
            'shift_id' => [
                'required',
                'exists:shifts,id',
                function ($attribute, $value, $fail) {
                    $shift = Shift::find($value);
                    if ($shift && !$shift->isAvailable()) {
                        $fail('The selected shift is not available for offers.');
                    }
                }
            ],
            'agency_employee_id' => [
                'required',
                'exists:agency_employees,id',
                function ($attribute, $value, $fail) {
                    $agent = $this->user()->agent;
                    if ($agent && $agent->agency_id !== \App\Models\AgencyEmployee::find($value)->agency_id) {
                        $fail('The selected agency employee does not belong to your agency.');
                    }
                }
            ],
            'expires_at' => 'required|date|after:now|before_or_equal:now()->addDays(7)'
        ];
    }

    public function messages(): array
    {
        return [
            'expires_at.before_or_equal' => 'Offer expiry cannot be more than 7 days from now.',
        ];
    }
}
