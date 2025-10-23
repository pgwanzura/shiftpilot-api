<?php

namespace App\Jobs;

use App\Models\ShiftOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyEmployee implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public ShiftOffer $shiftOffer)
    {
    }

    public function handle(): void
    {
        $employee = $this->shiftOffer->employee;

        $employee->user->notify(new \App\Notifications\ShiftOfferSentNotification($this->shiftOffer));

        logger("Employee notified about shift offer: {$this->shiftOffer->id}");
    }
}
