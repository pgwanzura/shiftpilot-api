<?php

namespace App\Providers;

use App\Events\Assignment\AssignmentCancelled;
use App\Events\Assignment\AssignmentCompleted;
use App\Events\Assignment\AssignmentCreated;
use App\Events\Assignment\AssignmentStatusChanged;
use App\Events\AvailabilityUpdated;
use App\Events\InvoiceGenerated;
use App\Events\InvoicePaid;
use App\Events\Location\LocationCreated;
use App\Events\Location\LocationUpdated;
use App\Events\Payroll\PayrollGenerated;
use App\Events\PayoutProcessed;
use App\Events\Shift\ShiftAssigned;
use App\Events\Shift\ShiftCancelled;
use App\Events\Shift\ShiftCompleted;
use App\Events\Shift\ShiftCreated;
use App\Events\Shift\ShiftOffered;
use App\Events\Shift\ShiftOfferAccepted;
use App\Events\Shift\ShiftOfferRejected;
use App\Events\Shift\ShiftOfferSent;
use App\Events\ShiftRequest\ShiftRequestCreated;
use App\Events\ShiftRequest\ShiftRequestPublished;
use App\Events\SubscriptionRenewed;
use App\Events\TimeOff\TimeOffApproved;
use App\Events\TimeOff\TimeOffRejected;
use App\Events\TimeOff\TimeOffRequested;
use App\Events\Timesheet\TimesheetAgencyApproved;
use App\Events\Timesheet\TimesheetEmployerApproved;
use App\Events\Timesheet\TimesheetSubmitted;
use App\Events\UserRegistered;
use App\Listeners\Assignment\HandleAssignmentCancellation;
use App\Listeners\Assignment\HandleAssignmentCompletion;
use App\Listeners\Assignment\SendAssignmentCreatedNotifications;
use App\Listeners\Assignment\SendAssignmentStatusChangeNotifications;
use App\Listeners\AuditLogListener;
use App\Listeners\LogLocationActivity;
use App\Listeners\LogShiftActivity;
use App\Listeners\LogTimesheetActivity;
use App\Listeners\NotifyAgencyOfNewLocation;
use App\Listeners\ProcessAvailabilityUpdated;
use App\Listeners\ProcessInvoiceGenerated;
use App\Listeners\ProcessInvoicePaid;
use App\Listeners\ProcessPayoutProcessed;
use App\Listeners\ProcessShiftAssigned;
use App\Listeners\ProcessShiftCompleted;
use App\Listeners\ProcessShiftOfferAccepted;
use App\Listeners\ProcessShiftOfferRejected;
use App\Listeners\ProcessShiftOfferSent;
use App\Listeners\ProcessShiftOffered;
use App\Listeners\ProcessShiftRequested;
use App\Listeners\ProcessSubscriptionRenewed;
use App\Listeners\ProcessTimeOffApproved;
use App\Listeners\ProcessTimeOffRequested;
use App\Listeners\ProcessTimesheetAgencyApproved;
use App\Listeners\ProcessTimesheetEmployerApproved;
use App\Listeners\ProcessTimesheetSubmitted;
use App\Listeners\SendInvoiceNotification;
use App\Listeners\SendSystemNotifications;
use App\Listeners\SendUserWelcomeNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        LocationCreated::class => [
            LogLocationActivity::class,
            NotifyAgencyOfNewLocation::class,
            AuditLogListener::class,
        ],
        LocationUpdated::class => [
            LogLocationActivity::class,
            AuditLogListener::class,
        ],
        UserRegistered::class => [
            SendUserWelcomeNotification::class,
            AuditLogListener::class,
        ],
        ShiftCreated::class => [
            LogShiftActivity::class,
            AuditLogListener::class,
            SendSystemNotifications::class,
        ],
        ShiftCancelled::class => [
            LogShiftActivity::class,
            AuditLogListener::class,
            SendSystemNotifications::class,
        ],
        ShiftRequestCreated::class => [
            ProcessShiftRequested::class,
            AuditLogListener::class,
        ],
        ShiftRequestPublished::class => [
            AuditLogListener::class,
            SendSystemNotifications::class,
        ],
        ShiftOffered::class => [
            ProcessShiftOffered::class,
            AuditLogListener::class,
        ],
        ShiftAssigned::class => [
            ProcessShiftAssigned::class,
            AuditLogListener::class,
            SendSystemNotifications::class,
        ],
        ShiftCompleted::class => [
            ProcessShiftCompleted::class,
            AuditLogListener::class,
            SendSystemNotifications::class,
        ],
        ShiftOfferSent::class => [
            ProcessShiftOfferSent::class,
            AuditLogListener::class,
        ],
        ShiftOfferAccepted::class => [
            ProcessShiftOfferAccepted::class,
            AuditLogListener::class,
            SendSystemNotifications::class,
        ],
        ShiftOfferRejected::class => [
            ProcessShiftOfferRejected::class,
            AuditLogListener::class,
            SendSystemNotifications::class,
        ],
        TimesheetSubmitted::class => [
            LogTimesheetActivity::class,
            ProcessTimesheetSubmitted::class,
            AuditLogListener::class,
            SendSystemNotifications::class,
        ],
        TimesheetAgencyApproved::class => [
            ProcessTimesheetAgencyApproved::class,
            AuditLogListener::class,
            SendSystemNotifications::class,
        ],
        TimesheetEmployerApproved::class => [
            ProcessTimesheetEmployerApproved::class,
            AuditLogListener::class,
            SendSystemNotifications::class,
        ],
        InvoiceGenerated::class => [
            SendInvoiceNotification::class,
            ProcessInvoiceGenerated::class,
            AuditLogListener::class,
        ],
        InvoicePaid::class => [
            ProcessInvoicePaid::class,
            AuditLogListener::class,
        ],
        PayrollGenerated::class => [
            AuditLogListener::class,
            SendSystemNotifications::class,
        ],
        PayoutProcessed::class => [
            ProcessPayoutProcessed::class,
            AuditLogListener::class,
            SendSystemNotifications::class,
        ],
        AvailabilityUpdated::class => [
            ProcessAvailabilityUpdated::class,
            AuditLogListener::class,
        ],
        TimeOffRequested::class => [
            ProcessTimeOffRequested::class,
            AuditLogListener::class,
            SendSystemNotifications::class,
        ],
        TimeOffApproved::class => [
            ProcessTimeOffApproved::class,
            AuditLogListener::class,
            SendSystemNotifications::class,
        ],
        TimeOffRejected::class => [
            AuditLogListener::class,
            SendSystemNotifications::class,
        ],
        SubscriptionRenewed::class => [
            ProcessSubscriptionRenewed::class,
            AuditLogListener::class,
            SendSystemNotifications::class,
        ],
        AssignmentCreated::class => [
            SendAssignmentCreatedNotifications::class,
            AuditLogListener::class,
            SendSystemNotifications::class,
        ],
        AssignmentStatusChanged::class => [
            SendAssignmentStatusChangeNotifications::class,
            AuditLogListener::class,
            SendSystemNotifications::class,
        ],
        AssignmentCompleted::class => [
            HandleAssignmentCompletion::class,
            AuditLogListener::class,
            SendSystemNotifications::class,
        ],
        AssignmentCancelled::class => [
            HandleAssignmentCancellation::class,
            AuditLogListener::class,
            SendSystemNotifications::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
