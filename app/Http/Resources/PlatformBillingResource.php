<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlatformBillingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'commission_rate' => $this->commission_rate,
            'transaction_fee_flat' => $this->transaction_fee_flat,
            'transaction_fee_percent' => $this->transaction_fee_percent,
            'payout_schedule_days' => $this->payout_schedule_days,
            'tax_vat_rate_percent' => $this->tax_vat_rate_percent,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
