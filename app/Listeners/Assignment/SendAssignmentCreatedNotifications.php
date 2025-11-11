<?php
// app/Listeners/SendAssignmentCreatedNotifications.php

namespace App\Listeners\Assignment;

use App\Events\AssignmentCreated;
use App\Models\User;
use App\Notifications\AssignmentCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendAssignmentCreatedNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(AssignmentCreated $event): void
    {
        $assignment = $event->assignment;

        // Notify agency admins and agents
        $agencyUsers = User::whereHas('agent', function ($query) use ($assignment) {
            $query->where('agency_id', $assignment->agencyEmployee->agency_id);
        })->orWhereHas('agency', function ($query) use ($assignment) {
            $query->where('id', $assignment->agencyEmployee->agency_id);
        })->get();

        foreach ($agencyUsers as $user) {
            $user->notify(new AssignmentCreatedNotification($assignment, 'agency'));
        }

        // Notify employer admins and contacts
        $employerUsers = User::whereHas('employerUser', function ($query) use ($assignment) {
            $query->where('employer_id', $assignment->contract->employer_id);
        })->orWhereHas('contact', function ($query) use ($assignment) {
            $query->where('employer_id', $assignment->contract->employer_id);
        })->get();

        foreach ($employerUsers as $user) {
            $user->notify(new AssignmentCreatedNotification($assignment, 'employer'));
        }

        // Notify the assigned employee
        $employeeUser = $assignment->agencyEmployee->employee->user;
        $employeeUser->notify(new AssignmentCreatedNotification($assignment, 'employee'));
    }
}
