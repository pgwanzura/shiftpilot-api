<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendUserWelcomeNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private NotificationService $notificationService)
    {
    }

    public function handle(UserRegistered $event): void
    {
        $user = $event->user;

        $this->notificationService->createNotification(
            $user,
            'user.welcome',
            [
                'name' => $user->name,
                'role' => $user->role,
            ],
            ['email', 'in_app']
        );
    }
}
