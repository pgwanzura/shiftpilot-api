<?php

namespace App\Http\Requests\AgencyAssignmentResponse;

use App\Models\AgencyAssignmentResponse;
use Illuminate\Foundation\Http\FormRequest;

class CreateAgencyAssignmentResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', AgencyAssignmentResponse::class);
    }

    public function rules(): array
    {
        return [
            'assignment_id' => 'required|exists:assignments,id',
            'agency_id' => 'sometimes|exists:agencies,id',
            'proposal_text' => 'required|string|min:10|max:2000',
            'proposed_rate' => 'required|numeric|min:0|max:10000',
            'estimated_hours' => 'required|integer|min:1|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'assignment_id.required' => 'The assignment is required.',
            'assignment_id.exists' => 'The selected assignment does not exist.',
            'proposal_text.required' => 'A proposal description is required.',
            'proposal_text.min' => 'The proposal must be at least 10 characters.',
            'proposed_rate.required' => 'A proposed rate is required.',
            'proposed_rate.numeric' => 'The proposed rate must be a valid number.',
            'estimated_hours.required' => 'Estimated hours are required.',
            'estimated_hours.integer' => 'Estimated hours must be a whole number.',
        ];
    }
}
