<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agency_id' => $this->agency_id,
            'period_start' => $this->period_start,
            'period_end' => $this->period_end,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'provider_payout_id' => $this->provider_payout_id,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'agency' => new AgencyResource($this->whenLoaded('agency')),
            'payrolls' => PayrollResource::collection($this->whenLoaded('payrolls')),
        ];
    }
}
