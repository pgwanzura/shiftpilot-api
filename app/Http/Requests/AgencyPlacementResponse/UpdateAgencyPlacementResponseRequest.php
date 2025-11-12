<?php

namespace App\Http\Requests\AgencyPlacementResponse;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgencyPlacementResponseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'shift_request_id' => 'sometimes|exists:shift_requests,id',
            'agency_id' => 'sometimes|exists:agencies,id',
            'proposed_employee_id' => 'nullable|exists:employees,id',
            'proposed_rate' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:pending,accepted,rejected,withdrawn',
            'notes' => 'nullable|string',
        ];
    }
}
