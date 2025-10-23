<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class SetAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\EmployeeAvailability::class);
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:recurring,one_time',
            'day_of_week' => 'required_if:type,recurring|in:mon,tue,wed,thu,fri,sat,sun',
            'start_date' => 'required_if:type,one_time|date',
            'end_date' => 'required_if:type,one_time|date|after:start_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'timezone' => 'required|string',
            'status' => 'required|in:available,unavailable,preferred',
            'priority' => 'nullable|integer|min:1|max:10',
            'location_preference' => 'nullable|array',
            'max_shift_length_hours' => 'nullable|integer|min:1',
            'min_shift_length_hours' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ];
    }
}
