<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \App\Events\Location\LocationCreated::class => [
            \App\Listeners\LogLocationActivity::class,
            \App\Listeners\NotifyAgencyOfNewLocation::class,
        ],
        \App\Events\Location\LocationUpdated::class => [
            \App\Listeners\LogLocationActivity::class,
        ],

        \App\Events\UserRegistered::class => [
            \App\Listeners\SendUserWelcomeNotification::class,
        ],

        \App\Events\Shift\ShiftCreated::class => [
            \App\Listeners\LogShiftActivity::class,
        ],
        \App\Events\Shift\ShiftCancelled::class => [
            \App\Listeners\LogShiftActivity::class,
        ],
        \App\Events\Shift\ShiftRequested::class => [
            \App\Listeners\ProcessShiftRequested::class,
        ],
        \App\Events\Shift\ShiftOffered::class => [
            \App\Listeners\ProcessShiftOffered::class,
        ],
        \App\Events\Shift\ShiftAssigned::class => [
            \App\Listeners\ProcessShiftAssigned::class,
        ],
        \App\Events\Shift\ShiftCompleted::class => [
            \App\Listeners\ProcessShiftCompleted::class,
        ],

        \App\Events\Shift\ShiftOfferSent::class => [
            \App\Listeners\ProcessShiftOfferSent::class,
        ],
        \App\Events\Shift\ShiftOfferAccepted::class => [
            \App\Listeners\ProcessShiftOfferAccepted::class,
        ],
        \App\Events\Shift\ShiftOfferRejected::class => [
            \App\Listeners\ProcessShiftOfferRejected::class,
        ],

        \App\Events\Timesheet\TimesheetSubmitted::class => [
            \App\Listeners\LogTimesheetActivity::class,
            \App\Listeners\ProcessTimesheetSubmitted::class,
        ],
        \App\Events\Timesheet\TimesheetApproved::class => [
            \App\Listeners\LogTimesheetActivity::class,
        ],
        \App\Events\Timesheet\TimesheetRejected::class => [
            \App\Listeners\LogTimesheetActivity::class,
        ],
        \App\Events\Timesheet\TimesheetAgencyApproved::class => [
            \App\Listeners\ProcessTimesheetAgencyApproved::class,
        ],
        \App\Events\Timesheet\TimesheetEmployerApproved::class => [
            \App\Listeners\ProcessTimesheetEmployerApproved::class,
        ],

        \App\Events\InvoiceGenerated::class => [
            \App\Listeners\SendInvoiceNotification::class,
            \App\Listeners\ProcessInvoiceGenerated::class,
        ],
        \App\Events\InvoicePaid::class => [
            \App\Listeners\ProcessInvoicePaid::class,
        ],
        \App\Events\PayoutProcessed::class => [
            \App\Listeners\ProcessPayoutProcessed::class,
        ],

        // Payment Events
        \App\Events\PaymentLogged::class => [
            // Add payment logging listeners
        ],
        \App\Events\PaymentConfirmed::class => [
            // Add payment confirmation listeners
        ],

        // Notification Events
        \App\Events\NotificationSent::class => [
            // Add notification sent listeners
        ],

        // Availability & Time Off Events
        \App\Events\AvailabilityUpdated::class => [
            \App\Listeners\ProcessAvailabilityUpdated::class,
        ],
        \App\Events\TimeOff\TimeOffRequested::class => [
            \App\Listeners\ProcessTimeOffRequested::class,
        ],
        \App\Events\TimeOff\TimeOffApproved::class => [
            \App\Listeners\ProcessTimeOffApproved::class,
        ],

        \App\Events\SubscriptionRenewed::class => [
            \App\Listeners\ProcessSubscriptionRenewed::class,
        ],

        \App\Events\Assignment\AssignmentCreated::class => [
            \App\Listeners\Assignment\SendAssignmentCreatedNotifications::class,
        ],
        \App\Events\Assignment\AssignmentStatusChanged::class => [
            \App\Listeners\Assignment\SendAssignmentStatusChangeNotifications::class,
        ],
        \App\Events\Assignment\AssignmentCompleted::class => [
            \App\Listeners\Assignment\HandleAssignmentCompletion::class,
        ],
        \App\Events\Assignment\AssignmentExtended::class => [
            // Add listeners for extension events
        ],
        \App\Events\Assignment\AssignmentCancelled::class => [
            \App\Listeners\Assignment\HandleAssignmentCancellation::class,
        ],
    ];

    protected $subscribe = [
        \App\Listeners\LogLocationActivity::class,
        \App\Listeners\LogShiftActivity::class,
        \App\Listeners\LogTimesheetActivity::class,
    ];

    public function boot(): void
    {
        //
    }
}
