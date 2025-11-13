<?php

namespace App\Policies;

use App\Models\AgencyBranch;
use App\Models\User;

class AgencyBranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') ||
            $user->hasRole('agency_admin') ||
            $user->hasRole('agent');
    }

    public function view(User $user, AgencyBranch $branch): bool
    {
        return $user->hasRole('super_admin') ||
            ($user->hasRole('agency_admin') && $this->isAgencyUser($user, $branch->agency)) ||
            ($user->hasRole('agent') && $this->isBranchAgent($user, $branch));
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') ||
            $user->hasRole('agency_admin');
    }

    public function update(User $user, AgencyBranch $branch): bool
    {
        return $user->hasRole('super_admin') ||
            ($user->hasRole('agency_admin') && $this->isAgencyUser($user, $branch->agency));
    }

    public function delete(User $user, AgencyBranch $branch): bool
    {
        if ($branch->is_head_office) {
            return false;
        }

        return $user->hasRole('super_admin') ||
            ($user->hasRole('agency_admin') && $this->isAgencyUser($user, $branch->agency));
    }

    public function manageHeadOffice(User $user, AgencyBranch $branch): bool
    {
        return $user->hasRole('super_admin') ||
            ($user->hasRole('agency_admin') && $this->isAgencyUser($user, $branch->agency));
    }

    private function isAgencyUser(User $user, \App\Models\Agency $agency): bool
    {
        return $agency->user_id === $user->id ||
            $agency->agents()->where('user_id', $user->id)->exists();
    }

    private function isBranchAgent(User $user, AgencyBranch $branch): bool
    {
        return $branch->agents()->where('user_id', $user->id)->exists();
    }
}
