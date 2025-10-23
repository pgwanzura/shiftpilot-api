<?php

namespace App\Jobs;

use App\Models\Shift;
use App\Models\Timesheet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateTimesheet implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public Shift $shift)
    {
    }

    public function handle(): void
    {
        // Check if timesheet already exists
        $existingTimesheet = Timesheet::where('shift_id', $this->shift->id)->first();

        if (!$existingTimesheet) {
            Timesheet::create([
                'shift_id' => $this->shift->id,
                'employee_id' => $this->shift->employee_id,
                'status' => 'pending',
            ]);

            logger("Timesheet created for shift: {$this->shift->id}");
        } else {
            logger("Timesheet already exists for shift: {$this->shift->id}");
        }
    }
}
