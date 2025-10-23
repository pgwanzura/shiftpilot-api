<?php

namespace App\Jobs;

use App\Models\Shift;
use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LockCandidate implements ShouldQueue
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
        // Add employee to shift's meta to indicate they're being considered
        $currentMeta = $this->shift->meta ?? [];
        $lockedCandidates = $currentMeta['locked_candidates'] ?? [];

        $lockedCandidates[] = [
            'employee_id' => $this->employee->id,
            'locked_at' => now()->toISOString(),
            'expires_at' => now()->addHours(24)->toISOString(), // Lock for 24 hours
        ];

        $this->shift->update([
            'meta' => array_merge($currentMeta, ['locked_candidates' => $lockedCandidates]),
        ]);

        logger("Candidate locked: Employee {$this->employee->id} for shift {$this->shift->id}");
    }
}
