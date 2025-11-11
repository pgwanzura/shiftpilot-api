<?php
// app/Listeners/SendAssignmentStatusChangeNotifications.php

namespace App\Listeners\Assignment;

use App\Events\AssignmentStatusChanged;
use App\Models\User;
use App\Notifications\AssignmentStatusChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendAssignmentStatusChangeNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(AssignmentStatusChanged $event): void
    {
        $assignment = $event->assignment;

        // Notify agency users
        $agencyUsers = User::whereHas('agent', function ($query) use ($assignment) {
            $query->where('agency_id', $assignment->agencyEmployee->agency_id);
        })->orWhereHas('agency', function ($query) use ($assignment) {
            $query->where('id', $assignment->agencyEmployee->agency_id);
        })->get();

        foreach ($agencyUsers as $user) {
            $user->notify(new AssignmentStatusChangedNotification(
                $assignment,
                $event->fromStatus,
                $event->toStatus,
                'agency'
            ));
        }

        // Notify employer users
        $employerUsers = User::whereHas('employerUser', function ($query) use ($assignment) {
            $query->where('employer_id', $assignment->contract->employer_id);
        })->orWhereHas('contact', function ($query) use ($assignment) {
            $query->where('employer_id', $assignment->contract->employer_id);
        })->get();

        foreach ($employerUsers as $user) {
            $user->notify(new AssignmentStatusChangedNotification(
                $assignment,
                $event->fromStatus,
                $event->toStatus,
                'employer'
            ));
        }

        // Notify the assigned employee
        $employeeUser = $assignment->agencyEmployee->employee->user;
        $employeeUser->notify(new AssignmentStatusChangedNotification(
            $assignment,
            $event->fromStatus,
            $event->toStatus,
            'employee'
        ));
    }
}
