<?php

namespace App\Jobs;

use App\Models\Shift;
use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateAssignment implements ShouldQueue
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
        // Update shift with employee assignment
        $this->shift->update([
            'employee_id' => $this->employee->id,
            'status' => 'assigned',
        ]);

        logger("Assignment created for shift: {$this->shift->id} to employee: {$this->employee->id}");
    }
}
