<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SchedulePayoutToAgency implements ShouldQueue
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
        $agency = $this->invoice->from;

        // Calculate payout amount (invoice amount minus platform commission)
        $platformBilling = \App\Models\PlatformBilling::first();
        $commissionAmount = $this->invoice->total_amount * ($platformBilling->commission_rate / 100);
        $payoutAmount = $this->invoice->total_amount - $commissionAmount;

        // Create payout record
        Payout::create([
            'agency_id' => $agency->id,
            'period_start' => now()->subWeek(), // Previous week
            'period_end' => now(),
            'total_amount' => $payoutAmount,
            'status' => 'processing',
        ]);

        logger("Payout scheduled for agency: {$agency->id}, amount: {$payoutAmount}");
    }
}
