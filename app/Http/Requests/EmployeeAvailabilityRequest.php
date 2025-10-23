<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isEmployee() || $this->user()->isAgencyAdmin() || $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|in:recurring,one_time',
            'day_of_week' => 'nullable|in:mon,tue,wed,thu,fri,sat,sun',
            'start_date' => 'nullable|date|required_if:type,one_time',
            'end_date' => 'nullable|date|after_or_equal:start_date|required_if:type,one_time',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'timezone' => 'nullable|string',
            'status' => 'nullable|in:available,unavailable,preferred',
            'priority' => 'nullable|integer|min:1|max:10',
            'location_preference' => 'nullable|array',
            'max_shift_length_hours' => 'nullable|integer|min:1',
            'min_shift_length_hours' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ];
    }
}
