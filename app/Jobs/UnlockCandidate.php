<?php

namespace App\Jobs;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UnlockCandidate implements ShouldQueue
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
        // Remove employee from any locked candidate lists
        $shifts = \App\Models\Shift::whereJsonContains('meta->locked_candidates', [
            'employee_id' => $this->employee->id
        ])->get();

        foreach ($shifts as $shift) {
            $currentMeta = $shift->meta ?? [];
            $lockedCandidates = $currentMeta['locked_candidates'] ?? [];

            $lockedCandidates = array_filter($lockedCandidates, function ($candidate) {
                return $candidate['employee_id'] !== $this->employee->id;
            });

            $shift->update([
                'meta' => array_merge($currentMeta, ['locked_candidates' => array_values($lockedCandidates)]),
            ]);
        }

        logger("Candidate unlocked: Employee {$this->employee->id}");
    }
}
