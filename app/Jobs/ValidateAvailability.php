<?php

namespace App\Jobs;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ValidateAvailability implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public Employee $employee)
    {
    }

    public function handle(): void
    {
        // Validate that availability doesn't conflict with existing shifts
        $conflicts = $this->findAvailabilityConflicts();

        if ($conflicts->isNotEmpty()) {
            logger("Availability conflicts found for employee: {$this->employee->id}");
            // Could trigger notifications or other actions here
        }

        logger("Availability validated for employee: {$this->employee->id}");
    }

    private function findAvailabilityConflicts()
    {
        return $this->employee->shifts()
            ->whereIn('status', ['assigned', 'offered'])
            ->whereHas('employeeAvailabilities', function ($query) {
                $query->where('status', 'unavailable');
            })
            ->get();
    }
}
