<?php

namespace App\Notifications;

use App\Models\Agency;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AgencyStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Agency $agency,
        public string $previousStatus
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Agency Status Updated')
            ->line('Your agency status has been changed from ' . $this->previousStatus . ' to ' . $this->agency->subscription_status->value)
            ->line('Agency: ' . $this->agency->name)
            ->action('View Agency', url('/agencies/' . $this->agency->id))
            ->line('If this was unexpected, please contact support.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'agency_id' => $this->agency->id,
            'agency_name' => $this->agency->name,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->agency->subscription_status->value,
            'message' => 'Agency status updated from ' . $this->previousStatus . ' to ' . $this->agency->subscription_status->value,
            'action_url' => '/agencies/' . $this->agency->id,
        ];
    }
}
