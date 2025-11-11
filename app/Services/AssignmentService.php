<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\User;
use App\Enums\AssignmentStatus;
use App\Events\AssignmentCreated;
use App\Events\AssignmentStatusChanged;
use App\Events\AssignmentCompleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssignmentService
{
    /**
     * Create a new assignment with business logic validation
     */
    public function createAssignment(array $data, User $createdBy): Assignment
    {
        return DB::transaction(function () use ($data, $createdBy) {
            // Validate business rules
            $this->validateAssignmentCreation($data);

            $assignment = Assignment::create(array_merge(
                $data,
                ['created_by_id' => $createdBy->id]
            ));

            // Dispatch event
            AssignmentCreated::dispatch($assignment);

            // Log the creation
            Log::info('Assignment created', [
                'assignment_id' => $assignment->id,
                'created_by' => $createdBy->id,
                'agency_employee_id' => $assignment->agency_employee_id,
                'contract_id' => $assignment->contract_id,
            ]);

            return $assignment->loadRelations();
        });
    }

    /**
     * Update assignment with business logic validation
     */
    public function updateAssignment(Assignment $assignment, array $data): Assignment
    {
        return DB::transaction(function () use ($assignment, $data) {
            $originalStatus = $assignment->status;

            $assignment->update($data);

            // Dispatch status change event if status changed
            if (isset($data['status']) && $assignment->status !== $originalStatus) {
                AssignmentStatusChanged::dispatch($assignment, $originalStatus, $assignment->status);
            }

            return $assignment->loadRelations();
        });
    }

    /**
     * Change assignment status with validation
     */
    public function changeStatus(Assignment $assignment, string $status, ?string $reason = null): Assignment
    {
        return DB::transaction(function () use ($assignment, $status, $reason) {
            $originalStatus = $assignment->status;
            $newStatus = AssignmentStatus::from($status);

            // Validate status transition
            $this->validateStatusTransition($assignment, $newStatus);

            $assignment->update([
                'status' => $newStatus,
                'notes' => $this->addStatusChangeNote($assignment->notes, $originalStatus, $newStatus, $reason)
            ]);

            // Dispatch events based on status
            AssignmentStatusChanged::dispatch($assignment, $originalStatus, $newStatus);

            if ($newStatus === AssignmentStatus::COMPLETED) {
                AssignmentCompleted::dispatch($assignment);
            }

            Log::info('Assignment status changed', [
                'assignment_id' => $assignment->id,
                'from_status' => $originalStatus->value,
                'to_status' => $newStatus->value,
                'reason' => $reason,
            ]);

            return $assignment->loadRelations();
        });
    }

    /**
     * Complete an assignment
     */
    public function completeAssignment(Assignment $assignment, ?string $reason = null): Assignment
    {
        return $this->changeStatus($assignment, AssignmentStatus::COMPLETED->value, $reason);
    }

    /**
     * Suspend an assignment
     */
    public function suspendAssignment(Assignment $assignment, string $reason): Assignment
    {
        return $this->changeStatus($assignment, AssignmentStatus::SUSPENDED->value, $reason);
    }

    /**
     * Reactivate a suspended assignment
     */
    public function reactivateAssignment(Assignment $assignment, string $reason): Assignment
    {
        return $this->changeStatus($assignment, AssignmentStatus::ACTIVE->value, $reason);
    }

    /**
     * Cancel an assignment
     */
    public function cancelAssignment(Assignment $assignment, string $reason): Assignment
    {
        return $this->changeStatus($assignment, AssignmentStatus::CANCELLED->value, $reason);
    }

    /**
     * Extend assignment end date
     */
    public function extendAssignment(Assignment $assignment, string $endDate, string $reason): Assignment
    {
        return DB::transaction(function () use ($assignment, $endDate, $reason) {
            $originalEndDate = $assignment->end_date;

            $assignment->update([
                'end_date' => $endDate,
                'notes' => $this->addExtensionNote($assignment->notes, $originalEndDate, $endDate, $reason)
            ]);

            Log::info('Assignment extended', [
                'assignment_id' => $assignment->id,
                'original_end_date' => $originalEndDate,
                'new_end_date' => $endDate,
                'reason' => $reason,
            ]);

            return $assignment->loadRelations();
        });
    }

    /**
     * Get assignment statistics
     */
    public function getStatistics(User $user, array $filters = []): array
    {
        $query = Assignment::query();

        // Apply user scope
        if ($user->isAgency()) {
            $query->whereHas('agencyEmployee', function ($q) use ($user) {
                $q->where('agency_id', $user->getAgencyId());
            });
        } elseif ($user->isEmployer()) {
            $query->whereHas('contract', function ($q) use ($user) {
                $q->where('employer_id', $user->getEmployerId());
            });
        }

        // Apply date filters
        if (isset($filters['start_date'])) {
            $query->where('start_date', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->where('start_date', '<=', $filters['end_date']);
        }

        $stats = [
            'total' => $query->count(),
            'active' => (clone $query)->where('status', AssignmentStatus::ACTIVE)->count(),
            'pending' => (clone $query)->where('status', AssignmentStatus::PENDING)->count(),
            'completed' => (clone $query)->where('status', AssignmentStatus::COMPLETED)->count(),
            'cancelled' => (clone $query)->where('status', AssignmentStatus::CANCELLED)->count(),
            'suspended' => (clone $query)->where('status', AssignmentStatus::SUSPENDED)->count(),
        ];

        // Add financial summary for authorized users
        if ($user->canViewFinancials()) {
            $stats['financial_summary'] = $this->getFinancialSummary($query);
        }

        // Add period info
        if (isset($filters['start_date']) || isset($filters['end_date'])) {
            $stats['period_start'] = $filters['start_date'] ?? null;
            $stats['period_end'] = $filters['end_date'] ?? null;
        }

        return $stats;
    }

    /**
     * Validate assignment creation business rules
     */
    private function validateAssignmentCreation(array $data): void
    {
        // Check if contract is active
        $contract = \App\Models\EmployerAgencyContract::find($data['contract_id']);
        if (!$contract || $contract->status !== 'active') {
            throw new \InvalidArgumentException('Assignment requires an active contract');
        }

        // Check if agency employee is active
        $agencyEmployee = \App\Models\AgencyEmployee::find($data['agency_employee_id']);
        if (!$agencyEmployee || $agencyEmployee->status !== 'active') {
            throw new \InvalidArgumentException('Selected agency employee is not active');
        }

        // Check for overlapping assignments
        $this->checkForOverlappingAssignments($data);
    }

    /**
     * Check for overlapping assignments for the same employee
     */
    private function checkForOverlappingAssignments(array $data): void
    {
        $overlapping = Assignment::where('agency_employee_id', $data['agency_employee_id'])
            ->where('status', AssignmentStatus::ACTIVE)
            ->where(function ($query) use ($data) {
                $query->where(function ($q) use ($data) {
                    $q->where('start_date', '<=', $data['start_date'])
                        ->where(function ($q2) use ($data) {
                            $q2->whereNull('end_date')
                                ->orWhere('end_date', '>=', $data['start_date']);
                        });
                });

                if ($data['end_date']) {
                    $query->orWhere(function ($q) use ($data) {
                        $q->where('start_date', '<=', $data['end_date'])
                            ->where(function ($q2) use ($data) {
                                $q2->whereNull('end_date')
                                    ->orWhere('end_date', '>=', $data['end_date']);
                            });
                    });
                }
            })
            ->exists();

        if ($overlapping) {
            throw new \InvalidArgumentException('Employee has overlapping active assignment');
        }
    }

    /**
     * Validate status transition
     */
    private function validateStatusTransition(Assignment $assignment, AssignmentStatus $newStatus): void
    {
        $validTransitions = [
            AssignmentStatus::PENDING->value => [AssignmentStatus::ACTIVE, AssignmentStatus::CANCELLED],
            AssignmentStatus::ACTIVE->value => [AssignmentStatus::COMPLETED, AssignmentStatus::SUSPENDED, AssignmentStatus::CANCELLED],
            AssignmentStatus::SUSPENDED->value => [AssignmentStatus::ACTIVE, AssignmentStatus::CANCELLED],
            AssignmentStatus::COMPLETED->value => [],
            AssignmentStatus::CANCELLED->value => [],
        ];

        $currentStatus = $assignment->status->value;

        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
            throw new \InvalidArgumentException(
                "Cannot transition from {$assignment->status->label()} to {$newStatus->label()}"
            );
        }
    }

    /**
     * Add status change note to assignment notes
     */
    private function addStatusChangeNote(?string $existingNotes, AssignmentStatus $from, AssignmentStatus $to, ?string $reason): string
    {
        $note = "\n\n--- Status Change ---\n";
        $note .= "Date: " . now()->format('Y-m-d H:i:s') . "\n";
        $note .= "From: {$from->label()}\n";
        $note .= "To: {$to->label()}\n";

        if ($reason) {
            $note .= "Reason: {$reason}\n";
        }

        return ($existingNotes ?? '') . $note;
    }

    /**
     * Add extension note to assignment notes
     */
    private function addExtensionNote(?string $existingNotes, ?string $from, string $to, string $reason): string
    {
        $note = "\n\n--- Assignment Extended ---\n";
        $note .= "Date: " . now()->format('Y-m-d H:i:s') . "\n";
        $note .= "From: " . ($from ?: 'Not set') . "\n";
        $note .= "To: {$to}\n";
        $note .= "Reason: {$reason}\n";

        return ($existingNotes ?? '') . $note;
    }

    /**
     * Get financial summary for assignments
     */
    private function getFinancialSummary($query): array
    {
        return [
            'total_agreed_value' => (float) $query->sum('agreed_rate'),
            'total_pay_value' => (float) $query->sum('pay_rate'),
            'total_margin' => (float) $query->sum('markup_amount'),
            'average_margin_percent' => (float) $query->avg('markup_percent'),
        ];
    }
}
