<?php

namespace App\Policies;

use App\Models\Agency;
use App\Models\User;

class AgencyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') ||
            $user->hasRole('agency_admin');
    }

    public function view(User $user, Agency $agency): bool
    {
        return $user->hasRole('super_admin') ||
            ($user->hasRole('agency_admin') && $this->isAgencyUser($user, $agency));
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') ||
            $user->hasRole('agency_admin');
    }

    public function update(User $user, Agency $agency): bool
    {
        return $user->hasRole('super_admin') ||
            ($user->hasRole('agency_admin') && $this->isAgencyUser($user, $agency));
    }

    public function delete(User $user, Agency $agency): bool
    {
        return $user->hasRole('super_admin');
    }

    public function restore(User $user, Agency $agency): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, Agency $agency): bool
    {
        return $user->hasRole('super_admin');
    }

    public function manageAgents(User $user, Agency $agency): bool
    {
        return $this->update($user, $agency);
    }

    public function viewFinancials(User $user, Agency $agency): bool
    {
        return $this->update($user, $agency);
    }

    public function updateSubscription(User $user, Agency $agency): bool
    {
        return $user->hasRole('super_admin');
    }

    private function isAgencyUser(User $user, Agency $agency): bool
    {
        return $agency->user_id === $user->id ||
            $agency->agents()->where('user_id', $user->id)->exists();
    }
}
