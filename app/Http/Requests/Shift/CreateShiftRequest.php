<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Shift::class);
    }

    public function rules(): array
    {
        return [
            'assignment_id' => 'required|exists:assignments,id',
            'location_id' => 'required|exists:locations,id',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'hourly_rate' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'meta' => 'nullable|array'
        ];
    }

    public function messages(): array
    {
        return [
            'assignment_id.required' => 'Assignment is required.',
            'assignment_id.exists' => 'Selected assignment does not exist.',
            'location_id.required' => 'Location is required.',
            'location_id.exists' => 'Selected location does not exist.',
            'start_time.required' => 'Start time is required.',
            'start_time.after' => 'Start time must be in the future.',
            'end_time.required' => 'End time is required.',
            'end_time.after' => 'End time must be after start time.',
            'hourly_rate.required' => 'Hourly rate is required.',
            'hourly_rate.min' => 'Hourly rate must be at least 0.'
        ];
    }
}
