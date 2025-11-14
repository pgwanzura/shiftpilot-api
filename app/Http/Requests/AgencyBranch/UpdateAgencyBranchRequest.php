<?php

namespace App\Http\Requests\AgencyBranch;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgencyBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('branch'));
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'branch_code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('agency_branches', 'branch_code')->ignore($this->route('branch')->id)
            ],
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'county' => 'nullable|string|max:100',
            'postcode' => 'nullable|string|max:20',
            'country' => 'nullable|string|size:2',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'status' => ['sometimes', 'required', Rule::in(\App\Enums\AgencyBranchStatus::values())],
            'opening_hours' => 'nullable|array',
            'services_offered' => 'nullable|array',
        ];
    }
}
