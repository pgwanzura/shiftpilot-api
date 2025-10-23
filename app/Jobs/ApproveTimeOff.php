<?php

namespace App\Jobs;

use App\Models\TimeOffRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ApproveTimeOff implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public TimeOffRequest $timeOffRequest)
    {
    }

    public function handle(): void
    {
        $this->timeOffRequest->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        // Update employee's availability to reflect time off
        $this->updateEmployeeAvailability();

        logger("Time off approved: {$this->timeOffRequest->id}");
    }

    private function updateEmployeeAvailability(): void
    {
        // Create temporary unavailability for the approved time off period
        $employee = $this->timeOffRequest->employee;

        // This would create EmployeeAvailability records with status 'unavailable'
        // for the time off period
    }
}
