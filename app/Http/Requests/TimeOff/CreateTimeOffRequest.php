<?php

namespace App\Http\Requests\TimeOff;

use Illuminate\Foundation\Http\FormRequest;

class CreateTimeOffRequest extends FormRequest
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
            'type' => 'required|in:vacation,sick,personal,bereavement,other',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'sometimes|string|max:1000',
            'attachments' => 'sometimes|array',
        ];
    }
}
