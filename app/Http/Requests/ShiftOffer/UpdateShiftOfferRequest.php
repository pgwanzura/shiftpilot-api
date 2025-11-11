<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ShiftOffer;

class UpdateShiftOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        $shiftOffer = ShiftOffer::findOrFail($this->route('shift_offer'));
        return $this->user()->can('update', $shiftOffer);
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:accepted,rejected',
            'response_notes' => 'nullable|string|max:500'
        ];
    }
}