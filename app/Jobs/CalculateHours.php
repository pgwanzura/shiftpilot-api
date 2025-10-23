<?php

namespace App\Jobs;

use App\Models\Timesheet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateHours implements ShouldQueue
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
        if ($this->timesheet->clock_in && $this->timesheet->clock_out) {
            $hoursWorked = $this->calculateHoursWorked();

            $this->timesheet->update([
                'hours_worked' => $hoursWorked,
            ]);

            logger("Hours calculated for timesheet: {$this->timesheet->id} - {$hoursWorked} hours");
        }
    }

    private function calculateHoursWorked(): float
    {
        $start = $this->timesheet->clock_in;
        $end = $this->timesheet->clock_out;

        $totalMinutes = $end->diffInMinutes($start);
        $breakMinutes = $this->timesheet->break_minutes;

        $workedMinutes = $totalMinutes - $breakMinutes;

        return round($workedMinutes / 60, 2); // Convert to hours with 2 decimal places
    }
}
