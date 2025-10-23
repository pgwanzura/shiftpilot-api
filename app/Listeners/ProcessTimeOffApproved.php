<?php

namespace App\Listeners;

use App\Events\TimeOffApproved;
use App\Jobs\ApproveTimeOff;
use App\Jobs\UpdateUnavailablePeriods;
use App\Services\WebhookService;
use App\Notifications\TimeOffApprovedNotification;

class ProcessTimeOffApproved
{
    public function handle(TimeOffApproved $event): void
    {
        ApproveTimeOff::dispatch($event->timeOffRequest);

        UpdateUnavailablePeriods::dispatch($event->timeOffRequest);

        $event->timeOffRequest->employee->user->notify(
            new TimeOffApprovedNotification($event->timeOffRequest)
        );

        logger("Time off approved: {$event->timeOffRequest->id}");

        WebhookService::dispatch('time_off.approved', [
            'time_off_request_id' => $event->timeOffRequest->id,
            'approved_by_id' => $event->timeOffRequest->approved_by_id,
            'approved_at' => $event->timeOffRequest->approved_at->toISOString(),
        ]);
    }
}
