<?php

namespace App\Http\Requests\RateCard;

use Illuminate\Foundation\Http\FormRequest;

class StoreRateCardRequest extends FormRequest
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
            'employer_id' => 'nullable|exists:employers,id',
            'agency_id' => 'nullable|exists:agencies,id',
            'role_key' => 'required|string|max:255',
            'location_id' => 'nullable|exists:locations,id',
            'day_of_week' => 'nullable|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'rate' => 'required|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ];
    }
}
