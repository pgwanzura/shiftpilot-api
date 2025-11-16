<?php

namespace App\Jobs;

use App\Mail\SystemNotificationMail;
use App\Models\SystemNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessSystemNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public SystemNotification $notification
    ) {}

    public function handle(): void
    {
        try {
            $this->sendNotification();
            $this->notification->markAsSent();
        } catch (\Exception $e) {
            Log::error('Failed to process system notification', [
                'notification_id' => $this->notification->id,
                'error' => $e->getMessage()
            ]);

            if ($this->attempts() < 3) {
                $this->release(60 * $this->attempts());
            }
        }
    }

    private function sendNotification(): void
    {
        match ($this->notification->channel) {
            'email' => $this->sendEmailNotification(),
            'sms' => $this->sendSmsNotification(),
            'push' => $this->sendPushNotification(),
            'in_app' => $this->sendInAppNotification(),
            default => throw new \InvalidArgumentException("Unsupported channel: {$this->notification->channel}")
        };
    }

    private function sendEmailNotification(): void
    {
        $user = $this->notification->user;
        $templateData = $this->getTemplateData();

        Mail::to($user->email)->send(
            new SystemNotificationMail(
                $this->notification->template_key,
                $templateData,
                $user
            )
        );

        Log::info('Email notification sent', [
            'notification_id' => $this->notification->id,
            'user_id' => $user->id,
            'email' => $user->email
        ]);
    }

    private function sendSmsNotification(): void
    {
        $user = $this->notification->user;

        if (!$user->phone) {
            Log::warning('Cannot send SMS notification - user has no phone number', [
                'notification_id' => $this->notification->id,
                'user_id' => $user->id
            ]);
            return;
        }

        $message = $this->formatSmsMessage();

        // Integrate with your SMS provider (Twilio, etc.)
        // Example: Twilio::sendSms($user->phone, $message);

        Log::info('SMS notification sent', [
            'notification_id' => $this->notification->id,
            'user_id' => $user->id,
            'phone' => $user->phone,
            'message' => $message
        ]);
    }

    private function sendPushNotification(): void
    {
        $user = $this->notification->user;
        $title = $this->getPushTitle();
        $body = $this->getPushBody();
        $data = $this->getPushData();

        // Integrate with your push notification service (Firebase, etc.)
        // Example: 
        // PushNotificationService::sendToUser($user->id, $title, $body, $data);

        Log::info('Push notification sent', [
            'notification_id' => $this->notification->id,
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body
        ]);
    }

    private function sendInAppNotification(): void
    {
        // In-app notifications are already stored in the database
        // This method can trigger real-time updates via WebSocket or other means

        // Example: Broadcast real-time notification
        // broadcast(new NewInAppNotification($this->notification));

        Log::info('In-app notification processed', [
            'notification_id' => $this->notification->id,
            'user_id' => $this->notification->user_id
        ]);
    }

    private function getTemplateData(): array
    {
        return array_merge(
            $this->notification->payload ?? [],
            [
                'user_name' => $this->notification->user->name,
                'notification_id' => $this->notification->id,
                'sent_at' => now()->format('Y-m-d H:i'),
            ]
        );
    }

    private function formatSmsMessage(): string
    {
        $templateKey = $this->notification->template_key;
        $data = $this->getTemplateData();

        $messages = [
            'shift.new_available' => "New shift available: {$data['location']} at {$data['start_time']}",
            'timesheet.submitted' => "Timesheet submitted for {$data['employee_name']} - {$data['hours_worked']} hours",
            'agency_response.submitted' => "New agency response from {$data['agency_name']}",
            'agency_response.accepted' => "Your agency response was accepted by {$data['employer_name']}",
            'agency_response.rejected' => "Your agency response was not accepted",
            'assignment.created' => "New assignment: {$data['location']} at {$data['shift_start']}",
            'assignment.approved' => "Assignment approved for {$data['shift_start']}",
            'timesheet.approved' => "Timesheet approved for {$data['shift_date']} - {$data['hours_worked']} hours",
            'timesheet.rejected' => "Timesheet rejected for {$data['shift_date']}. Reason: {$data['rejection_reason']}",
            'shift.cancelled' => "Shift cancelled: {$data['location']} at {$data['shift_start']}",
            'shift.modified' => "Shift modified: {$data['location']} at {$data['shift_start']}",
            'payment.processed' => "Payment processed: £{$data['amount']} for {$data['period']}",
            'contract.expiring' => "Contract expiring: {$data['contract_name']} on {$data['expiry_date']}",
            'user.welcome' => "Welcome to Staffing Platform, {$data['user_name']}!",
            'user.password_changed' => "Your password was changed successfully",
            'user.profile_updated' => "Your profile was updated successfully",
        ];

        return $messages[$templateKey] ?? 'You have a new notification from Staffing Platform';
    }

    private function getPushTitle(): string
    {
        $titles = [
            'shift.new_available' => 'New Shift Available',
            'timesheet.submitted' => 'Timesheet Submitted',
            'agency_response.submitted' => 'New Agency Response',
            'agency_response.accepted' => 'Response Accepted',
            'agency_response.rejected' => 'Response Not Accepted',
            'assignment.created' => 'New Assignment',
            'assignment.approved' => 'Assignment Approved',
            'timesheet.approved' => 'Timesheet Approved',
            'timesheet.rejected' => 'Timesheet Rejected',
            'shift.cancelled' => 'Shift Cancelled',
            'shift.modified' => 'Shift Modified',
            'payment.processed' => 'Payment Processed',
            'contract.expiring' => 'Contract Expiring',
            'user.welcome' => 'Welcome to Staffing Platform',
            'user.password_changed' => 'Password Changed',
            'user.profile_updated' => 'Profile Updated',
        ];

        return $titles[$this->notification->template_key] ?? 'Staffing Platform Notification';
    }

    private function getPushBody(): string
    {
        $data = $this->notification->payload ?? [];

        return match ($this->notification->template_key) {
            'shift.new_available' => "New shift at {$data['location']} - {$data['start_time']}",
            'timesheet.submitted' => "Timesheet submitted for {$data['employee_name']}",
            'agency_response.submitted' => "New response from {$data['agency_name']}",
            'agency_response.accepted' => "Your response was accepted",
            'agency_response.rejected' => "Your response was not accepted",
            'assignment.created' => "You have a new assignment",
            'assignment.approved' => "Your assignment was approved",
            'timesheet.approved' => "Your timesheet was approved",
            'timesheet.rejected' => "Your timesheet requires attention",
            'shift.cancelled' => "Your shift has been cancelled",
            'shift.modified' => "Your shift details have changed",
            'payment.processed' => "Payment of £{$data['amount']} processed",
            'contract.expiring' => "Contract {$data['contract_name']} expiring soon",
            'user.welcome' => "Welcome to Staffing Platform!",
            'user.password_changed' => "Your password was changed",
            'user.profile_updated' => "Your profile was updated",
            default => 'You have a new notification'
        };
    }

    private function getPushData(): array
    {
        return [
            'notification_id' => $this->notification->id,
            'template_key' => $this->notification->template_key,
            'payload' => $this->notification->payload,
            'click_action' => $this->getClickAction(),
        ];
    }

    private function getClickAction(): string
    {
        return match ($this->notification->template_key) {
            'shift.new_available', 'shift.cancelled', 'shift.modified' => '/shifts',
            'timesheet.submitted', 'timesheet.approved', 'timesheet.rejected' => '/timesheets',
            'agency_response.submitted', 'agency_response.accepted', 'agency_response.rejected' => '/agency-responses',
            'assignment.created', 'assignment.approved' => '/assignments',
            'payment.processed' => '/payments',
            'contract.expiring' => '/contracts',
            default => '/notifications'
        };
    }
}
