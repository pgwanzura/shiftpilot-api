<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgentRequest extends FormRequest
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
            'user_id' => 'sometimes|exists:users,id',
            'agency_id' => 'sometimes|exists:agencies,id',
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:agents,email,' . $this->route('agent')->id,
            'phone' => 'nullable|string|max:20',
            'permissions' => 'nullable|array',
        ];
    }
}
