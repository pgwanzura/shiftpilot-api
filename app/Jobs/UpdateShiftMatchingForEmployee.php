<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Notifications\EmployeePreference\ShiftMatchingImprovedNotification;
use App\Services\EmployeeMatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateShiftMatchingForEmployee implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $employeeId;

    public function __construct($employeeId)
    {
        $this->employeeId = $employeeId;
    }

    public function handle(EmployeeMatchingService $matchingService)
    {
        $employee = Employee::with(['preferences', 'user'])->find($this->employeeId);

        if (!$employee || !$employee->preferences) {
            return;
        }

        $matchingShifts = $matchingService->findMatchingShifts($employee->preferences);

        if (count($matchingShifts) > 0) {
            $employee->user->notify(
                new ShiftMatchingImprovedNotification($employee, count($matchingShifts))
            );
        }
    }
}
