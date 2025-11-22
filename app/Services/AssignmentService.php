<?php

namespace App\Services;

use App\Enums\AssignmentStatus;
use App\Enums\AssignmentType;
use App\Exceptions\AssignmentException;
use App\Models\Assignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AssignmentService
{
    public function createAssignment(array $data, User $creator): Assignment
    {
        return DB::transaction(function () use ($data, $creator) {
            $this->validateAssignmentCreation($data);

            $data['created_by_id'] = $creator->id;
            $data['status'] = AssignmentStatus::PENDING;

            $assignment = Assignment::create($data);

            if ($this->hasOverlappingAssignments($assignment)) {
                throw AssignmentException::overlappingAssignments();
            }

            return $assignment->fresh([
                'contract.employer',
                'contract.agency',
                'agencyEmployee.employee.user',
                'agencyEmployee.agency',
            ]);
        });
    }

    public function updateAssignment(Assignment $assignment, array $data): Assignment
    {
        return DB::transaction(function () use ($assignment, $data) {
            if (!$assignment->canBeUpdated()) {
                throw AssignmentException::cannotUpdate();
            }

            if (isset($data['agreed_rate']) && isset($data['pay_rate'])) {
                if ($data['agreed_rate'] < $data['pay_rate']) {
                    throw AssignmentException::invalidRates();
                }
            }

            $assignment->update($data);

            return $assignment->fresh();
        });
    }

    public function changeStatus(
        Assignment $assignment,
        AssignmentStatus $status,
        string $reason
    ): Assignment {
        return DB::transaction(function () use ($assignment, $status, $reason) {
            if (!$assignment->isValidStatusTransition($status)) {
                throw AssignmentException::invalidStatusTransition(
                    $assignment->status,
                    $status
                );
            }

            $assignment->updateStatus($status);

            $this->logStatusChange($assignment, $status, $reason);

            return $assignment->fresh();
        });
    }

    public function completeAssignment(Assignment $assignment, string $reason): Assignment
    {
        return $this->changeStatus($assignment, AssignmentStatus::COMPLETED, $reason);
    }

    public function suspendAssignment(Assignment $assignment, string $reason): Assignment
    {
        return $this->changeStatus($assignment, AssignmentStatus::SUSPENDED, $reason);
    }

    public function reactivateAssignment(Assignment $assignment, string $reason): Assignment
    {
        return $this->changeStatus($assignment, AssignmentStatus::ACTIVE, $reason);
    }

    public function cancelAssignment(Assignment $assignment, string $reason): Assignment
    {
        return $this->changeStatus($assignment, AssignmentStatus::CANCELLED, $reason);
    }

    public function extendAssignment(
        Assignment $assignment,
        string $newEndDate,
        string $reason
    ): Assignment {
        return DB::transaction(function () use ($assignment, $newEndDate, $reason) {
            if (!$assignment->isActive() || $assignment->end_date === null) {
                throw AssignmentException::cannotExtend();
            }

            $endDate = new \DateTime($newEndDate);

            if ($endDate <= $assignment->end_date) {
                throw AssignmentException::invalidExtensionDate();
            }

            $assignment->extendEndDate($endDate);

            $this->logExtension($assignment, $endDate, $reason);

            return $assignment->fresh();
        });
    }

    public function getStatistics(User $user, array $filters): array
    {
        $query = Assignment::query();

        if (!$user->isSuperAdmin()) {
            if ($user->isAgency()) {
                $agencyId = $user->getAgencyId();
                if ($agencyId) {
                    $query->forAgency($agencyId);
                }
            } elseif ($user->isEmployer()) {
                $employerId = $user->getEmployerId();
                if ($employerId) {
                    $query->forEmployer($employerId);
                }
            }
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->dateRange($filters['start_date'], $filters['end_date']);
        }

        if (isset($filters['agency_id'])) {
            $query->forAgency($filters['agency_id']);
        }

        if (isset($filters['employer_id'])) {
            $query->forEmployer($filters['employer_id']);
        }

        return [
            'total' => $query->count(),
            'active' => (clone $query)->active()->count(),
            'pending' => (clone $query)->pending()->count(),
            'completed' => (clone $query)->completed()->count(),
            'cancelled' => (clone $query)->cancelled()->count(),
            'suspended' => (clone $query)->suspended()->count(),
            // 'direct' => (clone $query)->direct()->count(),
            // 'standard' => (clone $query)->standard()->count(),
        ];
    }

    protected function validateAssignmentCreation(array $data): void
    {
        if (isset($data['agreed_rate']) && isset($data['pay_rate'])) {
            if ($data['agreed_rate'] < $data['pay_rate']) {
                throw AssignmentException::invalidRates();
            }
        }

        if ($data['assignment_type'] === AssignmentType::STANDARD->value) {
            if (!isset($data['agency_response_id'])) {
                throw AssignmentException::missingAgencyResponse();
            }
        }
    }

    protected function hasOverlappingAssignments(Assignment $assignment): bool
    {
        return Assignment::where('agency_employee_id', $assignment->agency_employee_id)
            ->where('id', '!=', $assignment->id)
            ->whereIn('status', [
                AssignmentStatus::ACTIVE,
                AssignmentStatus::PENDING,
                AssignmentStatus::SUSPENDED
            ])
            ->where(function ($query) use ($assignment) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $assignment->start_date);
            })
            ->where(function ($query) use ($assignment) {
                $endDate = $assignment->end_date ?? '9999-12-31';
                $query->where('start_date', '<=', $endDate);
            })
            ->exists();
    }

    protected function logStatusChange(
        Assignment $assignment,
        AssignmentStatus $status,
        string $reason
    ): void {
        activity()
            ->performedOn($assignment)
            ->withProperties([
                'status' => $status->value,
                'reason' => $reason,
                'previous_status' => $assignment->getOriginal('status')?->value
            ])
            ->log('status_changed');
    }

    protected function logExtension(
        Assignment $assignment,
        \DateTime $newEndDate,
        string $reason
    ): void {
        activity()
            ->performedOn($assignment)
            ->withProperties([
                'new_end_date' => $newEndDate->format('Y-m-d'),
                'previous_end_date' => $assignment->getOriginal('end_date')?->format('Y-m-d'),
                'reason' => $reason
            ])
            ->log('extended');
    }
}
