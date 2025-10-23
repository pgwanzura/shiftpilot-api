<?php

namespace App\Http\Requests\TimeOff;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimeOffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('timeOffRequest'));
    }

    public function rules(): array
    {
        return [
            'type' => 'sometimes|in:vacation,sick,personal,bereavement,other',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'reason' => 'nullable|string',
            'status' => 'sometimes|in:pending,approved,rejected,cancelled',
        ];
    }
}
