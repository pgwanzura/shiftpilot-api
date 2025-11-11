<?php

namespace App\Http\Requests;

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
            'assignment_id' => 'required|exists:assignments,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'recurrence_type' => 'required|in:weekly,biweekly,monthly',
            'effective_start_date' => 'nullable|date',
            'effective_end_date' => 'nullable|date|after:effective_start_date',
            'meta' => 'nullable|array'
        ];
    }
}
