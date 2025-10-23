<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlatformBillingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'commission_rate' => 'required|numeric|min:0|max:100',
            'transaction_fee_flat' => 'nullable|numeric|min:0',
            'transaction_fee_percent' => 'nullable|numeric|min:0|max:100',
            'payout_schedule_days' => 'nullable|integer|min:1',
            'tax_vat_rate_percent' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
