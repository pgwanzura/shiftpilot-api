<?php

namespace App\Jobs;

use App\Models\Timesheet;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateInvoice implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public Timesheet $timesheet)
    {
    }

    public function handle(): void
    {
        $shift = $this->timesheet->shift;

        $invoice = Invoice::create([
            'type' => 'employer_to_agency',
            'from_type' => 'agency',
            'from_id' => $shift->agency_id,
            'to_type' => 'employer',
            'to_id' => $shift->employer_id,
            'reference' => 'INV-' . now()->format('Ymd-His'),
            'subtotal' => $this->calculateSubtotal(),
            'total_amount' => $this->calculateTotal(),
            'due_date' => now()->addDays(14),
            'status' => 'pending',
            'metadata' => [
                'timesheet_id' => $this->timesheet->id,
                'shift_id' => $shift->id,
                'hours_worked' => $this->timesheet->hours_worked,
            ],
        ]);

        logger("Invoice generated: {$invoice->id} for timesheet: {$this->timesheet->id}");

        // Trigger invoice generated event
        event(new \App\Events\InvoiceGenerated($invoice));
    }

    private function calculateSubtotal(): float
    {
        $shift = $this->timesheet->shift;
        $hoursWorked = $this->timesheet->hours_worked ?? 0;
        $hourlyRate = $shift->hourly_rate ?? $shift->placement?->client_rate ?? 0;

        return $hoursWorked * $hourlyRate;
    }

    private function calculateTotal(): float
    {
        // Include taxes, fees, etc.
        return $this->calculateSubtotal();
    }
}
