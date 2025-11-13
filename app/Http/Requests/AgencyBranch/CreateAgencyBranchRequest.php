<?php

namespace App\Http\Requests\AgencyBranch;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\AgencyBranch::class);
    }

    public function rules(): array
    {
        return [
            'agency_id' => 'required|exists:agencies,id',
            'name' => 'required|string|max:255',
            'branch_code' => 'nullable|string|max:50|unique:agency_branches,branch_code',
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
            'status' => ['required', Rule::in(\App\Enums\AgencyBranchStatus::values())],
            'opening_hours' => 'nullable|array',
            'services_offered' => 'nullable|array',
        ];
    }
}
