<?php

namespace App\Jobs;

use App\Models\Shift;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ValidateShift implements ShouldQueue
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
        // Validate shift data
        $errors = [];

        if ($this->shift->start_time >= $this->shift->end_time) {
            $errors[] = 'End time must be after start time';
        }

        if ($this->shift->hourly_rate <= 0) {
            $errors[] = 'Hourly rate must be positive';
        }

        if (empty($errors)) {
            logger("Shift validated successfully: {$this->shift->id}");
        } else {
            logger("Shift validation failed: {$this->shift->id} - " . implode(', ', $errors));
            // Could update shift status or notify creator
        }
    }
}
