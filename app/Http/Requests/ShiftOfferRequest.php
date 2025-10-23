<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShiftOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->isMethod('POST')) {
            return $this->user()->isAgencyAdmin() || $this->user()->isAgent() || $this->user()->isSuperAdmin();
        }

        // For updates (accepting/rejecting offers)
        return $this->user()->isEmployee() || $this->user()->isAgencyAdmin() || $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        $rules = [
            'shift_id' => 'required|exists:shifts,id',
            'employee_id' => 'required|exists:employees,id',
            'offered_by_id' => 'required|exists:users,id',
            'status' => 'nullable|in:pending,accepted,rejected,expired',
            'expires_at' => 'required|date',
            'response_notes' => 'nullable|string',
        ];

        if ($this->isMethod('PATCH') || $this->isMethod('PUT')) {
            // Only allow status and response_notes updates
            return [
                'status' => 'required|in:accepted,rejected',
                'response_notes' => 'nullable|string',
            ];
        }

        return $rules;
    }
}
