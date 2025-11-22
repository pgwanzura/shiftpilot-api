<?php

namespace App\Policies;

use App\Enums\AssignmentStatus;
use App\Models\Assignment;
use App\Models\User;

class AssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() ||
            $user->isAgency() ||
            $user->isEmployer() ||
            $user->isEmployee();
    }

    public function view(User $user, Assignment $assignment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAgency()) {
            return $this->userOwnsAgencyAssignment($user, $assignment);
        }

        if ($user->isEmployer()) {
            return $this->userOwnsEmployerAssignment($user, $assignment);
        }

        if ($user->isEmployee()) {
            return $this->userIsAssignedEmployee($user, $assignment);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAgency();
    }

    public function update(User $user, Assignment $assignment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAgency() && $this->userOwnsAgencyAssignment($user, $assignment)) {
            return $assignment->canBeUpdated();
        }

        return false;
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAgencyAdmin() && $this->userOwnsAgencyAssignment($user, $assignment)) {
            return $assignment->canBeDeleted();
        }

        return false;
    }

    public function restore(User $user, Assignment $assignment): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDelete(User $user, Assignment $assignment): bool
    {
        return $user->isSuperAdmin();
    }

    public function changeStatus(User $user, Assignment $assignment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAgency() && $this->userOwnsAgencyAssignment($user, $assignment)) {
            return $assignment->canBeUpdated();
        }

        if (
            $user->isEmployer() &&
            $this->userOwnsEmployerAssignment($user, $assignment) &&
            $user->hasPermission('assignment:approve')
        ) {
            return in_array($assignment->status, [
                AssignmentStatus::ACTIVE,
                AssignmentStatus::PENDING
            ], true);
        }

        return false;
    }

    public function viewFinancials(User $user, Assignment $assignment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isAgency() && $this->userOwnsAgencyAssignment($user, $assignment);
    }

    public function manageShifts(User $user, Assignment $assignment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (
            $user->isAgency() &&
            $this->userOwnsAgencyAssignment($user, $assignment) &&
            $assignment->isActive()
        ) {
            return true;
        }

        if (
            $user->isEmployer() &&
            $this->userOwnsEmployerAssignment($user, $assignment) &&
            $assignment->isActive() &&
            $user->hasPermission('assignment:approve')
        ) {
            return true;
        }

        return false;
    }

    public function complete(User $user, Assignment $assignment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (
            $user->isAgency() &&
            $this->userOwnsAgencyAssignment($user, $assignment) &&
            $assignment->canBeCompleted()
        ) {
            return true;
        }

        if (
            $user->isEmployer() &&
            $this->userOwnsEmployerAssignment($user, $assignment) &&
            $assignment->canBeCompleted() &&
            $user->hasPermission('assignment:approve')
        ) {
            return true;
        }

        return false;
    }

    public function suspend(User $user, Assignment $assignment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isAgency() &&
            $this->userOwnsAgencyAssignment($user, $assignment) &&
            $assignment->canBeSuspended();
    }

    public function reactivate(User $user, Assignment $assignment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isAgency() &&
            $this->userOwnsAgencyAssignment($user, $assignment) &&
            $assignment->canBeReactivated();
    }

    public function cancel(User $user, Assignment $assignment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $canCancel = in_array($assignment->status, [
            AssignmentStatus::PENDING,
            AssignmentStatus::ACTIVE
        ], true);

        if (
            $user->isAgency() &&
            $this->userOwnsAgencyAssignment($user, $assignment) &&
            $canCancel
        ) {
            return true;
        }

        if (
            $user->isEmployer() &&
            $this->userOwnsEmployerAssignment($user, $assignment) &&
            $canCancel &&
            $user->hasPermission('assignment:approve')
        ) {
            return true;
        }

        return false;
    }

    public function extend(User $user, Assignment $assignment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $canExtend = $assignment->isActive() && $assignment->end_date !== null;

        if (
            $user->isAgency() &&
            $this->userOwnsAgencyAssignment($user, $assignment) &&
            $canExtend
        ) {
            return true;
        }

        if (
            $user->isEmployer() &&
            $this->userOwnsEmployerAssignment($user, $assignment) &&
            $canExtend &&
            $user->hasPermission('assignment:approve')
        ) {
            return true;
        }

        return false;
    }

    public function viewAnalytics(User $user, Assignment $assignment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAgency() && $this->userOwnsAgencyAssignment($user, $assignment)) {
            return true;
        }

        if ($user->isEmployer() && $this->userOwnsEmployerAssignment($user, $assignment)) {
            return true;
        }

        return false;
    }

    public function generateReports(User $user, Assignment $assignment): bool
    {
        return $this->viewAnalytics($user, $assignment);
    }

    public function manageNotes(User $user, Assignment $assignment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAgency() && $this->userOwnsAgencyAssignment($user, $assignment)) {
            return true;
        }

        if ($user->isEmployer() && $this->userOwnsEmployerAssignment($user, $assignment)) {
            return true;
        }

        return false;
    }

    public function viewHistory(User $user, Assignment $assignment): bool
    {
        return $this->view($user, $assignment);
    }

    public function clone(User $user, Assignment $assignment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isAgency() &&
            $this->userOwnsAgencyAssignment($user, $assignment) &&
            $assignment->isCompleted();
    }

    protected function userOwnsAgencyAssignment(User $user, Assignment $assignment): bool
    {
        $agencyId = $user->getAgencyId();

        if (!$agencyId) {
            return false;
        }

        return $assignment->agencyEmployee?->agency_id === $agencyId;
    }

    protected function userOwnsEmployerAssignment(User $user, Assignment $assignment): bool
    {
        $employerId = $user->getEmployerId();

        if (!$employerId) {
            return false;
        }

        return $assignment->contract?->employer_id === $employerId;
    }

    protected function userIsAssignedEmployee(User $user, Assignment $assignment): bool
    {
        return $assignment->agencyEmployee?->employee?->user_id === $user->id;
    }
}
