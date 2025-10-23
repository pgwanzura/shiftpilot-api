<?php

namespace App\Http\Requests\Shift;

use Illuminate\Foundation\Http\FormRequest;

class CreateShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Shift::class);
    }

    public function rules(): array
    {
        return [
            'employer_id' => 'required|exists:employers,id',
            'location_id' => 'required|exists:locations,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'hourly_rate' => 'nullable|numeric|min:0',
            'role_requirement' => 'nullable|string',
            'agency_id' => 'nullable|exists:agencies,id',
            'placement_id' => 'nullable|exists:placements,id',
        ];
    }
}
