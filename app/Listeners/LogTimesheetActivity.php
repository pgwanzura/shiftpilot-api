<?php

// app/Listeners/LogTimesheetActivity.php

namespace App\Listeners;

use App\Events\TimesheetSubmitted;
use App\Events\TimesheetApproved;
use App\Events\TimesheetRejected;
use App\Events\TimesheetUpdated;
use App\Events\TimesheetDeleted;
use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogTimesheetActivity
{
    public function handleTimesheetSubmitted(TimesheetSubmitted $event): void
    {
        AuditLog::create([
            'actor_type' => 'user',
            'actor_id' => $event->timesheet->employee->user_id,
            'action' => 'timesheet.submitted',
            'target_type' => 'timesheet',
            'target_id' => $event->timesheet->id,
            'payload' => [
                'hours_worked' => $event->timesheet->hours_worked,
                'shift_id' => $event->timesheet->shift_id,
            ],
        ]);
    }

    public function handleTimesheetApproved(TimesheetApproved $event): void
    {
        AuditLog::create([
            'actor_type' => 'user',
            'actor_id' => auth()->id(),
            'action' => "timesheet.{$event->approvedBy}_approved",
            'target_type' => 'timesheet',
            'target_id' => $event->timesheet->id,
            'payload' => [
                'approved_by' => $event->approvedBy,
            ],
        ]);
    }

    public function handleTimesheetRejected(TimesheetRejected $event): void
    {
        AuditLog::create([
            'actor_type' => 'user',
            'actor_id' => $event->rejectedBy->id,
            'action' => 'timesheet.rejected',
            'target_type' => 'timesheet',
            'target_id' => $event->timesheet->id,
            'payload' => [
                'reason' => $event->reason,
            ],
        ]);
    }

    public function handleTimesheetUpdated(TimesheetUpdated $event): void
    {
        AuditLog::create([
            'actor_type' => 'user',
            'actor_id' => auth()->id(),
            'action' => 'timesheet.updated',
            'target_type' => 'timesheet',
            'target_id' => $event->timesheet->id,
        ]);
    }

    public function handleTimesheetDeleted(TimesheetDeleted $event): void
    {
        AuditLog::create([
            'actor_type' => 'user',
            'actor_id' => auth()->id(),
            'action' => 'timesheet.deleted',
            'target_type' => 'timesheet',
            'target_id' => $event->timesheetId,
            'payload' => $event->timesheetData,
        ]);
    }

    public function subscribe($events): array
    {
        return [
            TimesheetSubmitted::class => 'handleTimesheetSubmitted',
            TimesheetApproved::class => 'handleTimesheetApproved',
            TimesheetRejected::class => 'handleTimesheetRejected',
            TimesheetUpdated::class => 'handleTimesheetUpdated',
            TimesheetDeleted::class => 'handleTimesheetDeleted',
        ];
    }
}
