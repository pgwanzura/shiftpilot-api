<?php
// app/Listeners/HandleAssignmentCancellation.php

namespace App\Listeners\Assignment;

use App\Events\AssignmentCancelled;
use App\Models\Shift;
use App\Enums\ShiftStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleAssignmentCancellation implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(AssignmentCancelled $event): void
    {
        $assignment = $event->assignment;

        // Cancel all future shifts for this assignment
        Shift::where('assignment_id', $assignment->id)
            ->where('start_time', '>', now())
            ->whereIn('status', [ShiftStatus::SCHEDULED, ShiftStatus::PENDING])
            ->update([
                'status' => ShiftStatus::CANCELLED,
                'notes' => \DB::raw("CONCAT(COALESCE(notes, ''), '\nCancelled due to assignment cancellation')")
            ]);

        // Send cancellation notifications to affected parties
        $this->sendCancellationNotifications($assignment, $event->reason);

        // Log the cancellation
        \App\Models\AuditLog::create([
            'action' => 'assignment_cancelled',
            'description' => "Assignment {$assignment->id} cancelled. Reason: {$event->reason}",
            'user_id' => auth()->id() ?? null,
            'target_type' => Assignment::class,
            'target_id' => $assignment->id,
            'metadata' => ['reason' => $event->reason]
        ]);
    }

    private function sendCancellationNotifications(Assignment $assignment, ?string $reason): void
    {
        // This would integrate with your notification system
        // For now, we'll just log it
        \Log::info('Assignment cancellation notifications sent', [
            'assignment_id' => $assignment->id,
            'reason' => $reason,
            'affected_shifts' => $assignment->shifts()
                ->where('start_time', '>', now())
                ->count()
        ]);
    }
}
