<?php

// app/Services/NotificationService.php

namespace App\Services;

use App\Models\User;
use App\Models\Shift;
use App\Models\Timesheet;
use App\Models\Notification;
use App\Events\NotificationSent;

class NotificationService
{
    public function notifyAgenciesOfNewShift(Shift $shift): void
    {
        $agencies = $shift->employer->agencies;

        foreach ($agencies as $agency) {
            foreach ($agency->agents as $agent) {
                $this->createNotification(
                    $agent->user,
                    'shift.new_available',
                    [
                        'shift_id' => $shift->id,
                        'employer_name' => $shift->employer->name,
                        'location' => $shift->location->name,
                        'start_time' => $shift->start_time->format('Y-m-d H:i'),
                    ],
                    ['email', 'in_app']
                );
            }
        }
    }

    public function notifyTimesheetSubmission(Timesheet $timesheet): void
    {
        $agencyAdmins = $timesheet->employee->agency->admins;

        foreach ($agencyAdmins as $admin) {
            $this->createNotification(
                $admin->user,
                'timesheet.submitted',
                [
                    'employee_name' => $timesheet->employee->user->name,
                    'shift_date' => $timesheet->shift->start_time->format('Y-m-d'),
                    'hours_worked' => $timesheet->hours_worked,
                ],
                ['email', 'in_app']
            );
        }
    }

    public function createNotification(User $user, string $templateKey, array $data, array $channels): Notification
    {
        $notification = Notification::create([
            'recipient_type' => 'user',
            'recipient_id' => $user->id,
            'channel' => implode(',', $channels),
            'template_key' => $templateKey,
            'payload' => $data,
            'is_read' => false,
        ]);

        event(new NotificationSent($notification));

        // Dispatch jobs for different channels
        if (in_array('email', $channels)) {
            \App\Jobs\SendEmailNotification::dispatch($notification);
        }

        if (in_array('sms', $channels)) {
            \App\Jobs\SendSmsNotification::dispatch($notification);
        }

        return $notification;
    }
}
