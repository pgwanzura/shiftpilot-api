<?php

namespace App\Jobs;

use App\Models\TimeOffRequest;
use App\Models\EmployeeAvailability;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateUnavailablePeriods implements ShouldQueue
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
        // Create unavailability records for the approved time off period
        EmployeeAvailability::create([
            'employee_id' => $this->timeOffRequest->employee_id,
            'type' => 'one_time',
            'start_date' => $this->timeOffRequest->start_date,
            'end_date' => $this->timeOffRequest->end_date,
            'start_time' => $this->timeOffRequest->start_time ?? '00:00:00',
            'end_time' => $this->timeOffRequest->end_time ?? '23:59:59',
            'status' => 'unavailable',
            'notes' => "Time off: {$this->timeOffRequest->type}",
        ]);

        logger("Unavailable periods updated for time off: {$this->timeOffRequest->id}");
    }
}
