<?php

namespace App\Policies;

use App\Models\Placement;
use App\Models\User;

class PlacementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isEmployer() || $user->isAgency() || $user->isAdmin();
    }

    public function view(User $user, Placement $placement): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isEmployer()) {
            return $placement->employer_id === $user->employer->id;
        }

        if ($user->isAgency()) {
            return $placement->target_agencies === 'all' ||
                in_array($user->agency->id, $placement->specific_agency_ids ?? []);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isEmployer();
    }

    public function update(User $user, Placement $placement): bool
    {
        return $user->isEmployer() &&
            $placement->employer_id === $user->employer->id &&
            $placement->isDraft();
    }

    public function delete(User $user, Placement $placement): bool
    {
        return $user->isEmployer() &&
            $placement->employer_id === $user->employer->id &&
            $placement->isDraft();
    }

    public function activate(User $user, Placement $placement): bool
    {
        return $user->isEmployer() &&
            $placement->employer_id === $user->employer->id &&
            $placement->canBeActivated();
    }
}
