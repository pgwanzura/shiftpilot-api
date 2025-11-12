<?php

namespace App\Http\Requests\AgencyEmployee;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgencyEmployeeRequest extends FormRequest
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
            'agency_id' => 'required|exists:agencies,id',
            'employee_id' => 'required|exists:employees,id',
            'position' => 'nullable|string|max:255',
            'pay_rate' => 'required|numeric|min:0',
            'employment_type' => 'required|in:temp,contract,temp_to_perm',
            'status' => 'sometimes|in:active,inactive,suspended,terminated',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after_or_equal:contract_start_date',
            'specializations' => 'nullable|array',
            'preferred_locations' => 'nullable|array',
            'max_weekly_hours' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ];
    }
}
