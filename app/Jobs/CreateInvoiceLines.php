<?php

namespace App\Jobs;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateInvoiceLines implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public Invoice $invoice)
    {
    }

    public function handle(): void
    {
        $lineItems = $this->generateLineItems();

        $this->invoice->update([
            'line_items' => $lineItems,
            'subtotal' => $this->calculateSubtotal($lineItems),
        ]);

        logger("Invoice lines created for invoice: {$this->invoice->id}");
    }

    private function generateLineItems(): array
    {
        $lineItems = [];

        // Generate line items based on invoice type
        switch ($this->invoice->type) {
            case 'employer_to_agency':
                $lineItems = $this->generateShiftLineItems();
                break;
            case 'agency_to_shiftpilot':
                $lineItems = $this->generateCommissionLineItems();
                break;
        }

        return $lineItems;
    }

    private function generateShiftLineItems(): array
    {
        // Generate line items for shift work
        return [
            [
                'description' => 'Shift work services',
                'quantity' => 1,
                'unit_price' => $this->invoice->total_amount,
                'total' => $this->invoice->total_amount,
            ]
        ];
    }

    private function generateCommissionLineItems(): array
    {
        // Generate line items for platform commission
        return [
            [
                'description' => 'Platform commission',
                'quantity' => 1,
                'unit_price' => $this->invoice->total_amount,
                'total' => $this->invoice->total_amount,
            ]
        ];
    }

    private function calculateSubtotal(array $lineItems): float
    {
        return collect($lineItems)->sum('total');
    }
}
