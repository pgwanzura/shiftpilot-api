<?php

namespace App\Jobs;

use App\Models\Shift;
use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyEmployer implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Shift $shift,
        public Employee $employee
    ) {
    }

    public function handle(): void
    {
        $employer = $this->shift->employer;
        $contacts = $employer->contacts;

        foreach ($contacts as $contact) {
            if ($contact->user) {
                $contact->user->notify(new \App\Notifications\ShiftOfferedNotification(
                    $this->shift,
                    $this->employee
                ));
            }
        }

        logger("Employer notified about shift offer: {$this->shift->id}");
    }
}
