<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class SetAvailabilityRequest extends FormRequest
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
            'availabilities' => 'required|array',
            'availabilities.*.type' => 'required|in:recurring,one_time',
            'availabilities.*.day_of_week' => 'required_if:availabilities.*.type,recurring|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'availabilities.*.start_date' => 'required_if:availabilities.*.type,one_time|date',
            'availabilities.*.end_date' => 'required_if:availabilities.*.type,one_time|date|after_or_equal:availabilities.*.start_date',
            'availabilities.*.start_time' => 'required|date_format:H:i',
            'availabilities.*.end_time' => 'required|date_format:H:i|after:availabilities.*.start_time',
            'availabilities.*.max_hours' => 'nullable|integer|min:0',
            'availabilities.*.flexible' => 'boolean',
            'availabilities.*.constraints' => 'nullable|array',
        ];
    }
}
