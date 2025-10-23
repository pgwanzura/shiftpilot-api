<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PayrollResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'agency_id' => $this->agency_id,
            'employee_id' => $this->employee_id,
            'period_start' => $this->period_start,
            'period_end' => $this->period_end,
            'total_hours' => $this->total_hours,
            'gross_pay' => $this->gross_pay,
            'taxes' => $this->taxes,
            'net_pay' => $this->net_pay,
            'status' => $this->status,
            'paid_at' => $this->paid_at,
            'payout_id' => $this->payout_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'agency' => $this->whenLoaded('agency'),
            'employee' => $this->whenLoaded('employee'),
            'payout' => $this->whenLoaded('payout'),
        ];
    }
}
