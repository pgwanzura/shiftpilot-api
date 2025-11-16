<?php

namespace App\Listeners;

use App\Events\Shift\ShiftCreated;
use App\Events\Shift\ShiftCancelled;
use App\Events\Shift\ShiftUpdated;
use App\Events\Timesheet\TimesheetSubmitted;
use App\Events\Timesheet\TimesheetApproved;
use App\Events\Timesheet\TimesheetRejected;
use App\Events\AgencyResponse\AgencyResponseSubmitted;
use App\Events\AgencyResponse\AgencyResponseAccepted;
use App\Events\AgencyResponse\AgencyResponseRejected;
use App\Events\Assignment\AssignmentCreated;
use App\Events\Assignment\AssignmentApproved;
use App\Events\UserRegistered;
use App\Events\UserPasswordChanged;
use App\Events\UserProfileUpdated;
use App\Events\PaymentProcessed;
use App\Events\ContractExpiringSoon;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendSystemNotifications implements ShouldQueue
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handleShiftCreated(ShiftCreated $event): void
    {
        $this->notificationService->notifyAgenciesOfNewShift($event->shift);
    }

    public function handleShiftCancelled(ShiftCancelled $event): void
    {
        $this->notificationService->notifyShiftCancelled($event->shift);
    }

    public function handleShiftUpdated(ShiftUpdated $event): void
    {
        $this->notificationService->notifyShiftModified($event->shift);
    }

    public function handleTimesheetSubmitted(TimesheetSubmitted $event): void
    {
        $this->notificationService->notifyTimesheetSubmission($event->timesheet);
    }

    public function handleTimesheetApproved(TimesheetApproved $event): void
    {
        $this->notificationService->notifyTimesheetApproved($event->timesheet);
    }

    public function handleTimesheetRejected(TimesheetRejected $event): void
    {
        $this->notificationService->notifyTimesheetRejected($event->timesheet);
    }

    public function handleAgencyResponseSubmitted(AgencyResponseSubmitted $event): void
    {
        $this->notificationService->notifyAgencyResponseSubmitted($event->agencyResponse);
    }

    public function handleAgencyResponseAccepted(AgencyResponseAccepted $event): void
    {
        $this->notificationService->notifyAgencyResponseAccepted($event->agencyResponse);
    }

    public function handleAgencyResponseRejected(AgencyResponseRejected $event): void
    {
        $this->notificationService->notifyAgencyResponseRejected($event->agencyResponse);
    }

    public function handleAssignmentCreated(AssignmentCreated $event): void
    {
        $this->notificationService->notifyAssignmentCreated($event->assignment);
    }

    public function handleAssignmentApproved($event): void
    {
        $this->notificationService->notifyAssignmentApproved($event->assignment);
    }

    public function handleUserRegistered(UserRegistered $event): void
    {
        $this->notificationService->notifyNewUserWelcome($event->user);
    }

    public function handleUserPasswordChanged(UserPasswordChanged $event): void
    {
        $this->notificationService->notifyPasswordChanged($event->user);
    }

    public function handleUserProfileUpdated(UserProfileUpdated $event): void
    {
        $this->notificationService->notifyProfileUpdated($event->user);
    }

    public function handlePaymentProcessed(PaymentProcessed $event): void
    {
        $this->notificationService->notifyPaymentProcessed($event->user, $event->amount, $event->period);
    }

    public function handleContractExpiringSoon(ContractExpiringSoon $event): void
    {
        $this->notificationService->notifyContractExpiring($event->user, $event->contractName, $event->expiryDate);
    }
}
