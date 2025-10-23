<?php

namespace App\Http\Requests\ShiftApproval;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShiftApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('approval'));
    }

    public function rules(): array
    {
        return [
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:pending,approved,rejected',
        ];
    }
}
