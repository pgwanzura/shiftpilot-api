<?php

namespace App\Listeners;

use App\Events\PayoutProcessed;
use App\Jobs\CreatePayrollRecords;
use App\Jobs\ExecutePayouts;
use App\Jobs\MarkPayrollPaid;
use App\Services\WebhookService;
use App\Notifications\PayoutProcessedNotification;

class ProcessPayoutProcessed
{
    public function handle(PayoutProcessed $event): void
    {
        CreatePayrollRecords::dispatch($event->payout);

        ExecutePayouts::dispatch($event->payout);

        MarkPayrollPaid::dispatch($event->payout);

        logger("Payout processed: {$event->payout->id}");

        WebhookService::dispatch('payout.processed', [
            'payout_id' => $event->payout->id,
            'agency_id' => $event->payout->agency_id,
            'amount' => $event->payout->total_amount,
            'period_start' => $event->payout->period_start->toISOString(),
            'period_end' => $event->payout->period_end->toISOString(),
            'processed_at' => now()->toISOString(),
        ]);
    }
}
