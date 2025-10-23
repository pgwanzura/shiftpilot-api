<?php

namespace App\Jobs;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MarkPayrollPaid implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public Payout $payout)
    {
    }

    public function handle(): void
    {
        // Update all payroll records for this payout to paid status
        $this->payout->payrolls()->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        logger("Payroll marked as paid for payout: {$this->payout->id}");

        // Trigger payout processed event
        event(new \App\Events\PayoutProcessed($this->payout));
    }
}
