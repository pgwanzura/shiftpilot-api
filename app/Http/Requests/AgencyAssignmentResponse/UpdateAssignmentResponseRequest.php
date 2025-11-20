<?php

namespace App\Http\Requests\AgencyAssignmentResponse;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\AgencyAssignmentResponse;

class UpdateAgencyAssignmentResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('response'));
    }

    public function rules(): array
    {
        return [
            'proposal_text' => 'sometimes|required|string|min:10|max:2000',
            'proposed_rate' => 'sometimes|required|numeric|min:0|max:10000',
            'estimated_hours' => 'sometimes|required|integer|min:1|max:500',
            'status' => 'sometimes|in:submitted,reviewed,accepted,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required_if' => 'A rejection reason is required when rejecting a response.',
            'status.in' => 'The status must be one of: submitted, reviewed, accepted, rejected.',
        ];
    }
}
