<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AgencyResponse;
use Illuminate\Auth\Access\HandlesAuthorization;

class AgencyResponsePolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
    }

    public function viewAny(User $user)
    {
        // Agency Admin and Agent can view responses for their agency
        return $user->isAgencyAdmin() || $user->isAgent();
    }

    public function view(User $user, AgencyResponse $agencyResponse)
    {
        // Agency Admin and Agent can view responses if they belong to their agency
        return ($user->isAgencyAdmin() || $user->isAgent()) && $user->agency->id === $agencyResponse->agency_id;
    }

    public function create(User $user)
    {
        // Agent can create responses
        return $user->isAgent();
    }

    public function update(User $user, AgencyResponse $agencyResponse)
    {
        // Only the submitting agency can update their response, and only if it's pending
        return $user->isAgent() && $user->agency->id === $agencyResponse->agency_id && $agencyResponse->isPending();
    }

    public function delete(User $user, AgencyResponse $agencyResponse)
    {
        // Only the submitting agency can delete their response, and only if it's pending
        return $user->isAgent() && $user->agency->id === $agencyResponse->agency_id && $agencyResponse->isPending();
    }
}

