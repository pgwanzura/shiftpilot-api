<?php

namespace App\Jobs;

use App\Models\Shift;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyAgencies implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public Shift $shift)
    {
    }

    public function handle(): void
    {
        // Get agencies linked to this employer
        $agencies = $this->shift->employer->agencies()
            ->wherePivot('status', 'approved')
            ->get();

        foreach ($agencies as $agency) {
            // Notify agency admins and agents
            $agencyAdmins = $agency->agents;

            foreach ($agencyAdmins as $admin) {
                // Send notification about new shift request
                $admin->user->notify(new \App\Notifications\ShiftRequestedNotification($this->shift));
            }
        }

        logger("Agencies notified about shift request: {$this->shift->id}");
    }
}
