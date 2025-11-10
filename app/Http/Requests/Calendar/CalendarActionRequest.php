<?php

namespace App\Http\Requests\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class CalendarActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action_type' => 'required|in:assign,offer,accept,reject,approve,complete,clock_in,clock_out,request_time_off',
            'parameters' => 'sometimes|array',
            'notes' => 'sometimes|string|max:500'
        ];
    }
}
