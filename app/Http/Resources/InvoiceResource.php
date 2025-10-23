<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'from_type' => $this->from_type,
            'from_id' => $this->from_id,
            'to_type' => $this->to_type,
            'to_id' => $this->to_id,
            'reference' => $this->reference,
            'line_items' => $this->line_items,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'due_date' => $this->due_date,
            'paid_at' => $this->paid_at,
            'payment_reference' => $this->payment_reference,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'from' => $this->whenLoaded('from'),
            'to' => $this->whenLoaded('to'),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}
