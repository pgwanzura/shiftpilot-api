<?php

namespace App\Http\Requests\Shift;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('shift'));
    }

    public function rules(): array
    {
        return [
            'location_id' => 'sometimes|exists:locations,id',
            'start_time' => 'sometimes|date',
            'end_time' => 'sometimes|date|after:start_time',
            'hourly_rate' => 'nullable|numeric|min:0',
            'role_requirement' => 'nullable|string',
            'status' => 'sometimes|in:open,offered,assigned,completed,agency_approved,employer_approved,billed,cancelled',
            'notes' => 'nullable|string',
        ];
    }
}
