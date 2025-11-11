<?php

namespace App\Policies;

use App\Models\Shift;
use App\Models\User;
use App\Enums\ShiftStatus;

class ShiftPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isAgency() || $user->isEmployer();
    }

    public function view(User $user, Shift $shift): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgency()) {
            return $shift->assignment->agencyEmployee->agency_id === $user->getAgencyId();
        }

        if ($user->isEmployer()) {
            return $shift->assignment->contract->employer_id === $user->getEmployerId();
        }

        if ($user->isEmployee()) {
            return $shift->assignment->agencyEmployee->employee->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isAgency() || $user->isEmployer();
    }

    public function update(User $user, Shift $shift): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgency()) {
            return $shift->assignment->agencyEmployee->agency_id === $user->getAgencyId() &&
                $shift->canBeUpdated();
        }

        if ($user->isEmployer()) {
            return $shift->assignment->contract->employer_id === $user->getEmployerId() &&
                $shift->canBeUpdated() &&
                $user->canApproveAssignments();
        }

        return false;
    }

    public function delete(User $user, Shift $shift): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgencyAdmin()) {
            return $shift->assignment->agencyEmployee->agency_id === $user->getAgencyId() &&
                $shift->isScheduled();
        }

        return false;
    }

    public function changeStatus(User $user, Shift $shift): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgency()) {
            return $shift->assignment->agencyEmployee->agency_id === $user->getAgencyId() &&
                $shift->canBeUpdated();
        }

        if ($user->isEmployer()) {
            return $shift->assignment->contract->employer_id === $user->getEmployerId() &&
                $shift->canBeUpdated() &&
                $user->canApproveAssignments();
        }

        if ($user->isEmployee()) {
            return $shift->assignment->agencyEmployee->employee->user_id === $user->id &&
                in_array($shift->status, [ShiftStatus::SCHEDULED, ShiftStatus::IN_PROGRESS]);
        }

        return false;
    }

    public function startShift(User $user, Shift $shift): bool
    {
        return $this->changeStatus($user, $shift) && $shift->canBeStarted();
    }

    public function completeShift(User $user, Shift $shift): bool
    {
        return $this->changeStatus($user, $shift) && $shift->canBeCompleted();
    }

    public function cancelShift(User $user, Shift $shift): bool
    {
        return $this->changeStatus($user, $shift) && $shift->canBeCancelled();
    }

    public function manageTimesheet(User $user, Shift $shift): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgency()) {
            return $shift->assignment->agencyEmployee->agency_id === $user->getAgencyId();
        }

        if ($user->isEmployer()) {
            return $shift->assignment->contract->employer_id === $user->getEmployerId();
        }

        if ($user->isEmployee()) {
            return $shift->assignment->agencyEmployee->employee->user_id === $user->id;
        }

        return false;
    }

    public function validate(User $user, Shift $shift): bool
    {
        return $user->isAdmin() || $user->isAgencyAdmin();
    }
}
