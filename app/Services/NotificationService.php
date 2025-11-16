<?php

namespace App\Services;

use App\Jobs\ProcessSystemNotification;
use App\Models\AgencyResponse;
use App\Models\Assignment;
use App\Models\Shift;
use App\Models\SystemNotification;
use App\Models\Timesheet;
use App\Models\User;

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

    public function notifyAgencyResponseSubmitted(AgencyResponse $response): void
    {
        $employerAdmins = $response->shiftRequest->employer->admins;

        foreach ($employerAdmins as $admin) {
            $this->createNotification(
                $admin->user,
                'agency_response.submitted',
                [
                    'agency_name' => $response->agency->name,
                    'shift_request_id' => $response->shiftRequest->id,
                    'proposed_rate' => $response->proposed_rate,
                ],
                ['email', 'in_app']
            );
        }
    }

    public function notifyAgencyResponseAccepted(AgencyResponse $response): void
    {
        $this->createNotification(
            $response->submittedBy,
            'agency_response.accepted',
            [
                'shift_request_id' => $response->shiftRequest->id,
                'employer_name' => $response->shiftRequest->employer->name,
            ],
            ['email', 'in_app']
        );
    }

    public function notifyAgencyResponseRejected(AgencyResponse $response): void
    {
        $this->createNotification(
            $response->submittedBy,
            'agency_response.rejected',
            [
                'shift_request_id' => $response->shiftRequest->id,
                'employer_name' => $response->shiftRequest->employer->name,
            ],
            ['email', 'in_app']
        );
    }

    public function notifyAssignmentCreated(Assignment $assignment): void
    {
        $this->createNotification(
            $assignment->employee->user,
            'assignment.created',
            [
                'assignment_id' => $assignment->id,
                'shift_start' => $assignment->shift->start_time->format('Y-m-d H:i'),
                'location' => $assignment->shift->location->name,
            ],
            ['email', 'in_app']
        );
    }

    public function notifyAssignmentApproved(Assignment $assignment): void
    {
        $this->createNotification(
            $assignment->employee->user,
            'assignment.approved',
            [
                'assignment_id' => $assignment->id,
                'shift_start' => $assignment->shift->start_time->format('Y-m-d H:i'),
            ],
            ['email', 'in_app']
        );
    }

    public function notifyTimesheetApproved(Timesheet $timesheet): void
    {
        $this->createNotification(
            $timesheet->employee->user,
            'timesheet.approved',
            [
                'timesheet_id' => $timesheet->id,
                'shift_date' => $timesheet->shift->start_time->format('Y-m-d'),
                'hours_worked' => $timesheet->hours_worked,
            ],
            ['email', 'in_app']
        );
    }

    public function notifyTimesheetRejected(Timesheet $timesheet): void
    {
        $this->createNotification(
            $timesheet->employee->user,
            'timesheet.rejected',
            [
                'timesheet_id' => $timesheet->id,
                'shift_date' => $timesheet->shift->start_time->format('Y-m-d'),
                'rejection_reason' => $timesheet->rejection_reason,
            ],
            ['email', 'in_app']
        );
    }

    public function notifyShiftCancelled(Shift $shift): void
    {
        if ($shift->assignment) {
            $this->createNotification(
                $shift->assignment->employee->user,
                'shift.cancelled',
                [
                    'shift_id' => $shift->id,
                    'shift_start' => $shift->start_time->format('Y-m-d H:i'),
                    'location' => $shift->location->name,
                ],
                ['email', 'in_app']
            );
        }

        $employerAdmins = $shift->employer->admins;
        foreach ($employerAdmins as $admin) {
            $this->createNotification(
                $admin->user,
                'shift.cancelled_admin',
                [
                    'shift_id' => $shift->id,
                    'shift_start' => $shift->start_time->format('Y-m-d H:i'),
                ],
                ['email', 'in_app']
            );
        }
    }

    public function notifyShiftModified(Shift $shift): void
    {
        if ($shift->assignment) {
            $this->createNotification(
                $shift->assignment->employee->user,
                'shift.modified',
                [
                    'shift_id' => $shift->id,
                    'shift_start' => $shift->start_time->format('Y-m-d H:i'),
                    'location' => $shift->location->name,
                ],
                ['email', 'in_app']
            );
        }
    }

    public function notifyPaymentProcessed(User $user, float $amount, string $period): void
    {
        $this->createNotification(
            $user,
            'payment.processed',
            [
                'amount' => $amount,
                'period' => $period,
                'processed_at' => now()->format('Y-m-d H:i'),
            ],
            ['email', 'in_app']
        );
    }

    public function notifyContractExpiring(User $user, string $contractName, string $expiryDate): void
    {
        $this->createNotification(
            $user,
            'contract.expiring',
            [
                'contract_name' => $contractName,
                'expiry_date' => $expiryDate,
                'days_until_expiry' => now()->diffInDays($expiryDate),
            ],
            ['email', 'in_app']
        );
    }

    public function notifyNewUserWelcome(User $user): void
    {
        $this->createNotification(
            $user,
            'user.welcome',
            [
                'user_name' => $user->name,
                'role' => $user->role,
            ],
            ['email', 'in_app']
        );
    }

    public function notifyPasswordChanged(User $user): void
    {
        $this->createNotification(
            $user,
            'user.password_changed',
            [
                'changed_at' => now()->format('Y-m-d H:i'),
            ],
            ['email']
        );
    }

    public function notifyProfileUpdated(User $user): void
    {
        $this->createNotification(
            $user,
            'user.profile_updated',
            [
                'updated_at' => now()->format('Y-m-d H:i'),
            ],
            ['email']
        );
    }

    public function createNotification(User $user, string $templateKey, array $data, array $channels): SystemNotification
    {
        $notification = SystemNotification::create([
            'user_id' => $user->id,
            'channel' => 'in_app',
            'template_key' => $templateKey,
            'payload' => $data,
            'is_read' => false,
        ]);

        $externalChannels = array_filter($channels, function ($channel) {
            return $channel !== 'in_app';
        });

        if (!empty($externalChannels)) {
            ProcessSystemNotification::dispatch($notification);
        }

        return $notification;
    }
}
