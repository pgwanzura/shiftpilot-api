<?php

namespace App\Http\Requests\ShiftTemplate;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShiftTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('template'));
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'day_of_week' => 'sometimes|in:mon,tue,wed,thu,fri,sat,sun',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'role_requirement' => 'nullable|string',
            'required_qualifications' => 'nullable|array',
            'hourly_rate' => 'nullable|numeric|min:0',
            'recurrence_type' => 'sometimes|in:weekly,biweekly,monthly',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'sometimes|in:active,inactive',
        ];
    }
}
