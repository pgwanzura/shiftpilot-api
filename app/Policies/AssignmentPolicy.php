<?php
// app/Policies/AssignmentPolicy.php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;
use App\Enums\AssignmentStatus;

class AssignmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isAgency() || $user->isEmployer();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Assignment $assignment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgency()) {
            return $assignment->agencyEmployee->agency_id === $user->getAgencyId();
        }

        if ($user->isEmployer()) {
            return $assignment->contract->employer_id === $user->getEmployerId();
        }

        if ($user->isEmployee()) {
            return $assignment->agencyEmployee->employee->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isAgency();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Assignment $assignment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgency()) {
            return $assignment->agencyEmployee->agency_id === $user->getAgencyId() &&
                $assignment->canBeUpdated();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Assignment $assignment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgencyAdmin()) {
            return $assignment->agencyEmployee->agency_id === $user->getAgencyId() &&
                $assignment->canBeDeleted();
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Assignment $assignment): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Assignment $assignment): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can change assignment status.
     */
    public function changeStatus(User $user, Assignment $assignment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgency()) {
            return $assignment->agencyEmployee->agency_id === $user->getAgencyId() &&
                $assignment->canChangeStatus();
        }

        // Employers can only complete or cancel assignments
        if ($user->isEmployer()) {
            return $assignment->contract->employer_id === $user->getEmployerId() &&
                in_array($assignment->status, [AssignmentStatus::ACTIVE, AssignmentStatus::PENDING]) &&
                $user->canApproveAssignments();
        }

        return false;
    }

    /**
     * Determine whether the user can view financial information.
     */
    public function viewFinancials(User $user, Assignment $assignment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgency()) {
            return $assignment->agencyEmployee->agency_id === $user->getAgencyId();
        }

        return false;
    }

    /**
     * Determine whether the user can manage shifts for the assignment.
     */
    public function manageShifts(User $user, Assignment $assignment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgency()) {
            return $assignment->agencyEmployee->agency_id === $user->getAgencyId() &&
                $assignment->isActive();
        }

        // Employers can manage shifts for their assignments
        if ($user->isEmployer()) {
            return $assignment->contract->employer_id === $user->getEmployerId() &&
                $assignment->isActive() &&
                $user->canApproveAssignments();
        }

        return false;
    }

    /**
     * Determine whether the user can complete the assignment.
     */
    public function complete(User $user, Assignment $assignment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Agency can complete their assignments
        if ($user->isAgency()) {
            return $assignment->agencyEmployee->agency_id === $user->getAgencyId() &&
                $assignment->canBeCompleted();
        }

        // Employers can also complete assignments
        if ($user->isEmployer()) {
            return $assignment->contract->employer_id === $user->getEmployerId() &&
                $assignment->canBeCompleted() &&
                $user->canApproveAssignments();
        }

        return false;
    }

    /**
     * Determine whether the user can suspend the assignment.
     */
    public function suspend(User $user, Assignment $assignment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Only agency can suspend assignments
        if ($user->isAgency()) {
            return $assignment->agencyEmployee->agency_id === $user->getAgencyId() &&
                $assignment->canBeSuspended();
        }

        return false;
    }

    /**
     * Determine whether the user can reactivate the assignment.
     */
    public function reactivate(User $user, Assignment $assignment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Only agency can reactivate suspended assignments
        if ($user->isAgency()) {
            return $assignment->agencyEmployee->agency_id === $user->getAgencyId() &&
                $assignment->canBeReactivated();
        }

        return false;
    }

    /**
     * Determine whether the user can cancel the assignment.
     */
    public function cancel(User $user, Assignment $assignment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgency()) {
            return $assignment->agencyEmployee->agency_id === $user->getAgencyId() &&
                in_array($assignment->status, [AssignmentStatus::PENDING, AssignmentStatus::ACTIVE]);
        }

        // Employers can cancel their assignments
        if ($user->isEmployer()) {
            return $assignment->contract->employer_id === $user->getEmployerId() &&
                in_array($assignment->status, [AssignmentStatus::PENDING, AssignmentStatus::ACTIVE]) &&
                $user->canApproveAssignments();
        }

        return false;
    }

    /**
     * Determine whether the user can extend the assignment.
     */
    public function extend(User $user, Assignment $assignment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgency()) {
            return $assignment->agencyEmployee->agency_id === $user->getAgencyId() &&
                $assignment->isActive() &&
                $assignment->end_date !== null;
        }

        // Employers can extend their assignments
        if ($user->isEmployer()) {
            return $assignment->contract->employer_id === $user->getEmployerId() &&
                $assignment->isActive() &&
                $assignment->end_date !== null &&
                $user->canApproveAssignments();
        }

        return false;
    }

    /**
     * Determine whether the user can view assignment analytics.
     */
    public function viewAnalytics(User $user, Assignment $assignment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgency()) {
            return $assignment->agencyEmployee->agency_id === $user->getAgencyId();
        }

        if ($user->isEmployer()) {
            return $assignment->contract->employer_id === $user->getEmployerId();
        }

        return false;
    }

    /**
     * Determine whether the user can generate reports for the assignment.
     */
    public function generateReports(User $user, Assignment $assignment): bool
    {
        return $this->viewAnalytics($user, $assignment);
    }

    /**
     * Determine whether the user can manage assignment notes.
     */
    public function manageNotes(User $user, Assignment $assignment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgency()) {
            return $assignment->agencyEmployee->agency_id === $user->getAgencyId();
        }

        if ($user->isEmployer()) {
            return $assignment->contract->employer_id === $user->getEmployerId();
        }

        return false;
    }

    /**
     * Determine whether the user can view assignment history.
     */
    public function viewHistory(User $user, Assignment $assignment): bool
    {
        return $this->view($user, $assignment);
    }

    /**
     * Determine whether the user can clone the assignment.
     */
    public function clone(User $user, Assignment $assignment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgency()) {
            return $assignment->agencyEmployee->agency_id === $user->getAgencyId() &&
                $assignment->isCompleted();
        }

        return false;
    }
}
