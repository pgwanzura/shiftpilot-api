<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAgencyAdmin() || $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'employer_id' => 'required|exists:employers,id',
            'agency_id' => 'required|exists:agencies,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'nullable|in:active,completed,terminated',
            'employee_rate' => 'nullable|numeric|min:0',
            'client_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ];
    }
}
