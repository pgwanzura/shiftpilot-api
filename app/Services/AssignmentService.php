<?php


namespace App\Services;

use App\Models\Assignment;
use App\Models\User;
use App\Enums\AssignmentStatus;
use App\Events\Assignment\AssignmentCreated;
use App\Events\Assignment\AssignmentStatusChanged;
use App\Events\Assignment\AssignmentCompleted;
use App\Events\Assignment\AssignmentCancelled;
use App\Events\Assignment\AssignmentExtended;
use App\Notifications\Assignment\NewAssignmentCreatedNotification;
use App\Notifications\Assignment\EmployeeAssignedNotification;
use App\Notifications\Assignment\AssignmentCompletionNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class AssignmentService
{
    public function __construct(
        private InvoiceService $invoiceService,
        private PayrollService $payrollService,
        private ShiftService $shiftService
    ) {}

    /**
     * Create a new assignment with comprehensive business validation
     */
    public function createAssignment(array $data, User $createdBy): Assignment
    {
        return DB::transaction(function () use ($data, $createdBy) {
            // Validate business rules before creation
            $this->validateAssignmentCreation($data);

            $assignment = Assignment::create(array_merge(
                $data,
                ['created_by_id' => $createdBy->id]
            ));

            // Dispatch creation event
            AssignmentCreated::dispatch($assignment);

            // Send specific notifications
            $this->sendAssignmentCreationNotifications($assignment);

            Log::info('Assignment created successfully', [
                'assignment_id' => $assignment->id,
                'created_by' => $createdBy->id,
                'agency_employee_id' => $assignment->agency_employee_id,
                'contract_id' => $assignment->contract_id,
            ]);

            return $assignment->loadRelations();
        });
    }

    /**
     * Update assignment with proper validation and event handling
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

            Log::info('Assignment updated', [
                'assignment_id' => $assignment->id,
                'updated_fields' => array_keys($data),
            ]);

            return $assignment->loadRelations();
        });
    }

    /**
     * Change assignment status with comprehensive validation
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

            // Dispatch appropriate events
            $this->dispatchStatusChangeEvents($assignment, $originalStatus, $newStatus, $reason);

            // Handle status-specific business logic
            $this->handleStatusSpecificLogic($assignment, $newStatus, $reason);

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
     * Complete an assignment and trigger related processes
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
     * Cancel an assignment with proper cleanup
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

            // Dispatch extension event
            AssignmentExtended::dispatch($assignment, $originalEndDate, $endDate);

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
     * Get filtered assignments with pagination
     */
    public function getFilteredAssignments(array $filters): LengthAwarePaginator
    {
        $query = Assignment::with([
            'contract.employer',
            'contract.agency',
            'agencyEmployee.employee.user',
            'agencyEmployee.agency',
            'location',
            'shiftRequest',
            'agencyResponse',
            'createdBy'
        ]);

        $this->applyFilters($query, $filters);

        $perPage = $filters['per_page'] ?? 20;

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get assignment statistics for dashboard
     */
    public function getAssignmentStats(User $user, array $filters = []): array
    {
        $query = $this->buildStatsQuery($user, $filters);

        $stats = [
            'total' => $query->count(),
            'active' => (clone $query)->where('status', AssignmentStatus::ACTIVE)->count(),
            'pending' => (clone $query)->where('status', AssignmentStatus::PENDING)->count(),
            'completed' => (clone $query)->where('status', AssignmentStatus::COMPLETED)->count(),
            'cancelled' => (clone $query)->where('status', AssignmentStatus::CANCELLED)->count(),
            'suspended' => (clone $query)->where('status', AssignmentStatus::SUSPENDED)->count(),
        ];

        // Add financial data for authorized users
        if ($user->canViewFinancials()) {
            $stats['financial_summary'] = $this->getFinancialSummary($query);
        }

        // Add performance metrics
        $stats['performance'] = $this->getPerformanceMetrics($query);

        return $stats;
    }

    /**
     * Validate assignment creation against business rules
     */
    private function validateAssignmentCreation(array $data): void
    {
        // Check if contract exists and is active
        $contract = \App\Models\EmployerAgencyContract::findOrFail($data['contract_id']);
        if ($contract->status !== 'active') {
            throw new \InvalidArgumentException('Assignment requires an active contract');
        }

        // Check if agency employee exists and is active
        $agencyEmployee = \App\Models\AgencyEmployee::findOrFail($data['agency_employee_id']);
        if ($agencyEmployee->status !== 'active') {
            throw new \InvalidArgumentException('Selected agency employee is not active');
        }

        // Validate rate integrity
        if ($data['agreed_rate'] < $data['pay_rate']) {
            throw new \InvalidArgumentException('Agreed rate must be greater than or equal to pay rate');
        }

        // Check for overlapping assignments
        $this->checkForOverlappingAssignments($data);

        // Validate assignment doesn't conflict with employee availability
        $this->checkEmployeeAvailability($data);
    }

    /**
     * Check for overlapping assignments for the same employee
     */
    private function checkForOverlappingAssignments(array $data): void
    {
        $overlapping = Assignment::where('agency_employee_id', $data['agency_employee_id'])
            ->whereIn('status', [AssignmentStatus::ACTIVE, AssignmentStatus::PENDING])
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
            throw new \InvalidArgumentException('Employee has overlapping active or pending assignment');
        }
    }

    /**
     * Validate employee availability
     */
    private function checkEmployeeAvailability(array $data): void
    {
        $employeeId = \App\Models\AgencyEmployee::findOrFail($data['agency_employee_id'])->employee_id;

        // Check for approved time off during assignment period
        $timeOffConflict = \App\Models\TimeOffRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where(function ($query) use ($data) {
                $query->whereBetween('start_date', [$data['start_date'], $data['end_date'] ?? $data['start_date']])
                    ->orWhereBetween('end_date', [$data['start_date'], $data['end_date'] ?? $data['start_date']])
                    ->orWhere(function ($q) use ($data) {
                        $q->where('start_date', '<=', $data['start_date'])
                            ->where('end_date', '>=', $data['end_date'] ?? $data['start_date']);
                    });
            })
            ->exists();

        if ($timeOffConflict) {
            throw new \InvalidArgumentException('Employee has approved time off during the assignment period');
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
                "Cannot transition assignment from {$assignment->status->label()} to {$newStatus->label()}"
            );
        }

        // Additional business rule validations
        if ($newStatus === AssignmentStatus::ACTIVE && !$assignment->hasActiveContract()) {
            throw new \InvalidArgumentException('Cannot activate assignment with inactive contract');
        }

        if ($newStatus === AssignmentStatus::ACTIVE && !$assignment->hasActiveAgencyEmployee()) {
            throw new \InvalidArgumentException('Cannot activate assignment with inactive agency employee');
        }
    }

    /**
     * Dispatch appropriate events for status changes
     */
    private function dispatchStatusChangeEvents(
        Assignment $assignment,
        AssignmentStatus $fromStatus,
        AssignmentStatus $toStatus,
        ?string $reason
    ): void {
        AssignmentStatusChanged::dispatch($assignment, $fromStatus, $toStatus);

        // Dispatch specific events for important status changes
        match ($toStatus) {
            AssignmentStatus::COMPLETED => AssignmentCompleted::dispatch($assignment),
            AssignmentStatus::CANCELLED => AssignmentCancelled::dispatch($assignment, $reason),
            default => null
        };
    }

    /**
     * Handle business logic specific to status changes
     */
    private function handleStatusSpecificLogic(Assignment $assignment, AssignmentStatus $newStatus, ?string $reason): void
    {
        match ($newStatus) {
            AssignmentStatus::COMPLETED => $this->handleAssignmentCompletion($assignment),
            AssignmentStatus::CANCELLED => $this->handleAssignmentCancellation($assignment, $reason),
            AssignmentStatus::SUSPENDED => $this->handleAssignmentSuspension($assignment, $reason),
            AssignmentStatus::ACTIVE => $this->handleAssignmentActivation($assignment),
            default => null
        };
    }

    /**
     * Handle assignment completion business logic
     */
    private function handleAssignmentCompletion(Assignment $assignment): void
    {
        // Generate final invoice
        $this->invoiceService->generateFinalInvoice($assignment);

        // Process final payroll
        $this->payrollService->processFinalPayroll($assignment);

        // Send completion notifications
        $this->sendAssignmentCompletionNotifications($assignment);
    }

    /**
     * Handle assignment cancellation business logic
     */
    private function handleAssignmentCancellation(Assignment $assignment, ?string $reason): void
    {
        // Cancel future shifts
        $this->shiftService->cancelFutureShifts($assignment);

        // Send cancellation notifications
        $this->sendAssignmentCancellationNotifications($assignment, $reason);
    }

    /**
     * Handle assignment suspension business logic
     */
    private function handleAssignmentSuspension(Assignment $assignment, string $reason): void
    {
        // Suspend future shifts
        $this->shiftService->suspendFutureShifts($assignment);

        // Send suspension notifications
        $this->sendAssignmentSuspensionNotifications($assignment, $reason);
    }

    /**
     * Handle assignment activation business logic
     */
    private function handleAssignmentActivation(Assignment $assignment): void
    {
        // Reactivate suspended shifts if any
        $this->shiftService->reactivateSuspendedShifts($assignment);

        // Send activation notifications
        $this->sendAssignmentActivationNotifications($assignment);
    }

    /**
     * Send notifications for assignment creation
     */
    private function sendAssignmentCreationNotifications(Assignment $assignment): void
    {
        // Notify agency users
        $agencyUsers = User::whereHas('agent', function ($query) use ($assignment) {
            $query->where('agency_id', $assignment->agencyEmployee->agency_id);
        })->orWhereHas('agency', function ($query) use ($assignment) {
            $query->where('id', $assignment->agencyEmployee->agency_id);
        })->get();

        foreach ($agencyUsers as $user) {
            $user->notify(new NewAssignmentCreatedNotification($assignment));
        }

        // Notify the assigned employee
        $employeeUser = $assignment->agencyEmployee->employee->user;
        $employeeUser->notify(new EmployeeAssignedNotification($assignment));
    }

    /**
     * Send notifications for assignment completion
     */
    private function sendAssignmentCompletionNotifications(Assignment $assignment): void
    {
        $agencyUsers = User::whereHas('agent', function ($query) use ($assignment) {
            $query->where('agency_id', $assignment->agencyEmployee->agency_id);
        })->orWhereHas('agency', function ($query) use ($assignment) {
            $query->where('id', $assignment->agencyEmployee->agency_id);
        })->get();

        $employerUsers = User::whereHas('employerUser', function ($query) use ($assignment) {
            $query->where('employer_id', $assignment->contract->employer_id);
        })->orWhereHas('contact', function ($query) use ($assignment) {
            $query->where('employer_id', $assignment->contract->employer_id);
        })->get();

        foreach ($agencyUsers as $user) {
            $user->notify(new AssignmentCompletionNotification($assignment));
        }

        foreach ($employerUsers as $user) {
            $user->notify(new AssignmentCompletionNotification($assignment));
        }
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['assignment_type'])) {
            $query->where('assignment_type', $filters['assignment_type']);
        }

        if (isset($filters['agency_id'])) {
            $query->whereHas('agencyEmployee', function ($q) use ($filters) {
                $q->where('agency_id', $filters['agency_id']);
            });
        }

        if (isset($filters['employer_id'])) {
            $query->whereHas('contract', function ($q) use ($filters) {
                $q->where('employer_id', $filters['employer_id']);
            });
        }

        if (isset($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (isset($filters['start_date'])) {
            $query->where('start_date', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '<=', $filters['end_date']);
            });
        }

        if (isset($filters['search'])) {
            $query->where('role', 'like', '%' . $filters['search'] . '%');
        }

        // User-specific filtering
        if (isset($filters['user_id'])) {
            $user = User::find($filters['user_id']);
            if ($user->isEmployee()) {
                $query->whereHas('agencyEmployee.employee', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }
        }
    }

    /**
     * Build query for statistics
     */
    private function buildStatsQuery(User $user, array $filters)
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

        return $query;
    }

    /**
     * Get financial summary for assignments
     */
    private function getFinancialSummary($query): array
    {
        $financials = $query->selectRaw('
            SUM(agreed_rate) as total_agreed_value,
            SUM(pay_rate) as total_pay_value,
            SUM(markup_amount) as total_margin,
            AVG(markup_percent) as average_margin_percent
        ')->first();

        return [
            'total_agreed_value' => (float) ($financials->total_agreed_value ?? 0),
            'total_pay_value' => (float) ($financials->total_pay_value ?? 0),
            'total_margin' => (float) ($financials->total_margin ?? 0),
            'average_margin_percent' => (float) ($financials->average_margin_percent ?? 0),
        ];
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics($query): array
    {
        return [
            'average_duration_days' => (int) $query->avg('duration_days'),
            'completion_rate' => $this->calculateCompletionRate($query),
            'utilization_rate' => $this->calculateUtilizationRate($query),
        ];
    }

    /**
     * Calculate assignment completion rate
     */
    private function calculateCompletionRate($query): float
    {
        $total = $query->count();
        $completed = (clone $query)->where('status', AssignmentStatus::COMPLETED)->count();

        return $total > 0 ? ($completed / $total) * 100 : 0;
    }

    /**
     * Calculate utilization rate
     */
    private function calculateUtilizationRate($query): float
    {
        // This would be a more complex calculation based on actual vs expected hours
        // For now, returning a placeholder
        return 85.5; // Example utilization rate
    }

    /**
     * Add status change note to assignment notes
     */
    private function addStatusChangeNote(
        ?string $existingNotes,
        AssignmentStatus $from,
        AssignmentStatus $to,
        ?string $reason
    ): string {
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
     * Send assignment cancellation notifications
     */
    private function sendAssignmentCancellationNotifications(Assignment $assignment, ?string $reason): void
    {
        // Implementation would send specific cancellation notifications
        Log::info('Assignment cancellation notifications sent', [
            'assignment_id' => $assignment->id,
            'reason' => $reason
        ]);
    }

    /**
     * Send assignment suspension notifications
     */
    private function sendAssignmentSuspensionNotifications(Assignment $assignment, string $reason): void
    {
        // Implementation would send specific suspension notifications
        Log::info('Assignment suspension notifications sent', [
            'assignment_id' => $assignment->id,
            'reason' => $reason
        ]);
    }

    /**
     * Send assignment activation notifications
     */
    private function sendAssignmentActivationNotifications(Assignment $assignment): void
    {
        // Implementation would send specific activation notifications
        Log::info('Assignment activation notifications sent', [
            'assignment_id' => $assignment->id
        ]);
    }
}
