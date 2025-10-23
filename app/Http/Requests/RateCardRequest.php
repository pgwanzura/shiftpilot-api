<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RateCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isEmployerAdmin() || $this->user()->isAgencyAdmin() || $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'employer_id' => 'nullable|exists:employers,id',
            'agency_id' => 'nullable|exists:agencies,id',
            'role_key' => 'required|string|max:255',
            'location_id' => 'nullable|exists:locations,id',
            'day_of_week' => 'nullable|in:mon,tue,wed,thu,fri,sat,sun',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'rate' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after:effective_from',
        ];
    }
}
