<?php

namespace App\Policies;

use App\Models\SystemNotification;
use App\Models\User;

class SystemNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SystemNotification $notification): bool
    {
        return $user->id === $notification->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAgencyAdmin() || $user->isEmployerAdmin();
    }

    public function update(User $user, SystemNotification $notification): bool
    {
        return $user->id === $notification->user_id;
    }

    public function delete(User $user, SystemNotification $notification): bool
    {
        return $user->id === $notification->user_id || $user->isSuperAdmin();
    }

    public function markAsRead(User $user, SystemNotification $notification): bool
    {
        return $user->id === $notification->user_id;
    }
}
