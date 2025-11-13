<?php

namespace App\Notifications;

use App\Models\Agency;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AgencyCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(public Agency $agency) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Agency Registration Complete')
            ->line('Your agency ' . $this->agency->name . ' has been successfully registered.')
            ->line('You can now start adding agents and employees to your agency.')
            ->action('Go to Dashboard', url('/agency/dashboard'))
            ->line('Thank you for using our platform!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'agency_id' => $this->agency->id,
            'agency_name' => $this->agency->name,
            'message' => 'Your agency has been successfully registered.',
            'action_url' => '/agency/dashboard',
        ];
    }
}
