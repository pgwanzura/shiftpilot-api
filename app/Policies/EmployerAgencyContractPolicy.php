<?php

namespace App\Policies;

use App\Models\User;
use App\Models\EmployerAgencyContract;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployerAgencyContractPolicy
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
        // Employer Admin or Agency Admin can view contracts
        return $user->isEmployerAdmin() || $user->isAgencyAdmin();
    }

    public function view(User $user, EmployerAgencyContract $contract)
    {
        // Employer Admin can view their own contracts
        if ($user->isEmployerAdmin() && $user->employer->id === $contract->employer_id) {
            return true;
        }

        // Agency Admin can view contracts they are part of
        if ($user->isAgencyAdmin() && $user->agency->id === $contract->agency_id) {
            return true;
        }

        return false;
    }

    public function create(User $user)
    {
        // Employer Admin or Agency Admin can create contracts
        return $user->isEmployerAdmin() || $user->isAgencyAdmin();
    }

    public function update(User $user, EmployerAgencyContract $contract)
    {
        // Only Employer Admin or Agency Admin related to the contract can update it
        return ($user->isEmployerAdmin() && $user->employer->id === $contract->employer_id) ||
               ($user->isAgencyAdmin() && $user->agency->id === $contract->agency_id);
    }

    public function delete(User $user, EmployerAgencyContract $contract)
    {
        // Only Employer Admin or Agency Admin related to the contract can delete it
        return ($user->isEmployerAdmin() && $user->employer->id === $contract->employer_id) ||
               ($user->isAgencyAdmin() && $user->agency->id === $contract->agency_id);
    }

    public function manage(User $user, EmployerAgencyContract $contract)
    {
        return $this->update($user, $contract);
    }
}
