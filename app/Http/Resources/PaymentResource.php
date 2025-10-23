<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'payer_type' => $this->payer_type,
            'payer_id' => $this->payer_id,
            'amount' => $this->amount,
            'method' => $this->method,
            'processor_id' => $this->processor_id,
            'status' => $this->status,
            'fee_amount' => $this->fee_amount,
            'net_amount' => $this->net_amount,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'invoice' => $this->whenLoaded('invoice'),
        ];
    }
}
