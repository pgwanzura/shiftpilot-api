<?php

namespace App\Jobs;

use App\Models\TimeOffRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckShiftConflicts implements ShouldQueue
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
        $employee = $this->timeOffRequest->employee;
        $conflictingShifts = $this->findConflictingShifts();

        if ($conflictingShifts->isNotEmpty()) {
            // Notify about conflicts
            $this->notifyAboutConflicts($conflictingShifts);

            logger("Shift conflicts found for time off request: {$this->timeOffRequest->id}");
        } else {
            logger("No shift conflicts found for time off request: {$this->timeOffRequest->id}");
        }
    }

    private function findConflictingShifts()
    {
        return $this->timeOffRequest->employee->shifts()
            ->whereBetween('start_time', [
                $this->timeOffRequest->start_date,
                $this->timeOffRequest->end_date
            ])
            ->whereIn('status', ['assigned', 'offered'])
            ->get();
    }

    private function notifyAboutConflicts($conflictingShifts): void
    {
        // Notify agency admins about the conflicts
        // This would trigger notifications to relevant parties
    }
}
