<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isEmployerAdmin() || $this->user()->isAgencyAdmin() || $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'employer_id' => 'required|exists:employers,id',
            'agency_id' => 'nullable|exists:agencies,id',
            'placement_id' => 'nullable|exists:placements,id',
            'employee_id' => 'nullable|exists:employees,id',
            'agent_id' => 'nullable|exists:agents,id',
            'location_id' => 'required|exists:locations,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'hourly_rate' => 'nullable|numeric|min:0',
            'status' => 'required|in:open,offered,assigned,completed,agency_approved,employer_approved,billed,cancelled',
            'created_by_type' => 'required|in:employer,agency',
            'created_by_id' => 'required|integer',
            'meta' => 'nullable|array',
            'notes' => 'nullable|string',
        ];
    }
}
