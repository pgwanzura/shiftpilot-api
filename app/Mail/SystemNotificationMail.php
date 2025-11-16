<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SystemNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $templateKey,
        public array $templateData,
        public User $user
    ) {}

    public function build(): self
    {
        $subject = $this->getEmailSubject();
        $view = $this->getEmailView();

        return $this->subject($subject)
            ->markdown($view)
            ->with($this->templateData);
    }

    private function getEmailSubject(): string
    {
        $subjects = [
            'shift.new_available' => 'New Shift Available',
            'timesheet.submitted' => 'Timesheet Submitted for Review',
            'agency_response.submitted' => 'New Agency Response Received',
            'agency_response.accepted' => 'Your Agency Response Was Accepted',
            'agency_response.rejected' => 'Your Agency Response Was Not Accepted',
            'assignment.created' => 'New Assignment Created',
            'assignment.approved' => 'Assignment Approved',
            'timesheet.approved' => 'Timesheet Approved',
            'timesheet.rejected' => 'Timesheet Requires Attention',
            'shift.cancelled' => 'Shift Cancellation Notice',
            'shift.modified' => 'Shift Details Updated',
            'payment.processed' => 'Payment Processed',
            'contract.expiring' => 'Contract Expiring Soon',
            'user.welcome' => 'Welcome to Staffing Platform',
            'user.password_changed' => 'Password Changed Successfully',
            'user.profile_updated' => 'Profile Updated Successfully',
        ];

        return $subjects[$this->templateKey] ?? 'Notification from Staffing Platform';
    }

    private function getEmailView(): string
    {
        $views = [
            'shift.new_available' => 'emails.shifts.new-available',
            'timesheet.submitted' => 'emails.timesheets.submitted',
            'agency_response.submitted' => 'emails.agency-responses.submitted',
            'agency_response.accepted' => 'emails.agency-responses.accepted',
            'agency_response.rejected' => 'emails.agency-responses.rejected',
            'assignment.created' => 'emails.assignments.created',
            'assignment.approved' => 'emails.assignments.approved',
            'timesheet.approved' => 'emails.timesheets.approved',
            'timesheet.rejected' => 'emails.timesheets.rejected',
            'shift.cancelled' => 'emails.shifts.cancelled',
            'shift.modified' => 'emails.shifts.modified',
            'payment.processed' => 'emails.payments.processed',
            'contract.expiring' => 'emails.contracts.expiring',
            'user.welcome' => 'emails.users.welcome',
            'user.password_changed' => 'emails.users.password-changed',
            'user.profile_updated' => 'emails.users.profile-updated',
        ];

        return $views[$this->templateKey] ?? 'emails.notifications.general';
    }
}
