<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShiftTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isEmployerAdmin() || $this->user()->isAgencyAdmin() || $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'employer_id' => 'required|exists:employers,id',
            'location_id' => 'required|exists:locations,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'day_of_week' => 'required|in:mon,tue,wed,thu,fri,sat,sun',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'role_requirement' => 'nullable|string|max:255',
            'required_qualifications' => 'nullable|array',
            'hourly_rate' => 'nullable|numeric|min:0',
            'recurrence_type' => 'nullable|in:weekly,biweekly,monthly',
            'status' => 'nullable|in:active,inactive',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'created_by_type' => 'required|in:employer,agency',
            'created_by_id' => 'required|integer',
            'meta' => 'nullable|array',
        ];
    }
}
