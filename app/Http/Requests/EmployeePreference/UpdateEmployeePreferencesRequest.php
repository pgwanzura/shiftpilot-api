<?php

namespace App\Http\Requests\EmployeePreference;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeePreferencesRequest extends CreateEmployeePreferencesRequest
{
    public function rules()
    {
        $rules = parent::rules();

        $rules['employee_id'] = 'sometimes|required|exists:employees,id';

        return $rules;
    }
}
