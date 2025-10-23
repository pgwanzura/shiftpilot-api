<?php

namespace App\Jobs;

use App\Models\Shift;
use App\Models\Employee;
use App\Models\Placement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreatePlacementIfMissing implements ShouldQueue
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
        $existingPlacement = Placement::where([
            'employee_id' => $this->employee->id,
            'employer_id' => $this->shift->employer_id,
            'agency_id' => $this->shift->agency_id,
        ])->where('status', 'active')->first();

        if (!$existingPlacement) {
            Placement::create([
                'employee_id' => $this->employee->id,
                'employer_id' => $this->shift->employer_id,
                'agency_id' => $this->shift->agency_id,
                'start_date' => now(),
                'status' => 'active',
                'employee_rate' => $this->employee->pay_rate,
                'client_rate' => $this->shift->hourly_rate,
            ]);

            logger("New placement created for employee: {$this->employee->id} with employer: {$this->shift->employer_id}");
        } else {
            logger("Existing placement found for employee: {$this->employee->id}");
        }
    }
}
