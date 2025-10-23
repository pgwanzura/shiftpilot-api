<?php

// app/Listeners/LogShiftActivity.php

namespace App\Listeners;

use App\Events\ShiftCreated;
use App\Events\ShiftCancelled;
use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogShiftActivity
{
    public function handleShiftCreated(ShiftCreated $event): void
    {
        AuditLog::create([
            'actor_type' => 'user',
            'actor_id' => auth()->id(),
            'action' => 'shift.created',
            'target_type' => 'shift',
            'target_id' => $event->shift->id,
            'payload' => [
                'employer_id' => $event->shift->employer_id,
                'start_time' => $event->shift->start_time,
                'end_time' => $event->shift->end_time,
            ],
        ]);
    }

    public function handleShiftCancelled(ShiftCancelled $event): void
    {
        AuditLog::create([
            'actor_type' => 'user',
            'actor_id' => $event->cancelledBy->id,
            'action' => 'shift.cancelled',
            'target_type' => 'shift',
            'target_id' => $event->shift->id,
            'payload' => [
                'reason' => $event->shift->meta['cancellation_reason'] ?? 'No reason provided',
            ],
        ]);
    }

    public function subscribe($events): array
    {
        return [
            ShiftCreated::class => 'handleShiftCreated',
            ShiftCancelled::class => 'handleShiftCancelled',
        ];
    }
}
