<?php

namespace App\Http\Requests\ShiftTemplate;

use Illuminate\Foundation\Http\FormRequest;

class CreateShiftTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\ShiftTemplate::class);
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
            'role_requirement' => 'nullable|string',
            'required_qualifications' => 'nullable|array',
            'hourly_rate' => 'nullable|numeric|min:0',
            'recurrence_type' => 'required|in:weekly,biweekly,monthly',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ];
    }
}
