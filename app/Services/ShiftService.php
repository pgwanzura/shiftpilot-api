<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\Assignment;
use App\Models\User;
use App\Enums\ShiftStatus;
use App\Events\Shift\ShiftCreated;
use App\Events\Shift\ShiftStatusChanged;
use App\Events\Shift\ShiftCancelled;
use App\Events\Shift\ShiftCompleted;
use App\Events\Shift\ShiftsCancelled;
use App\Notifications\Shift\ShiftScheduledNotification;
use App\Notifications\Shift\ShiftStatusChangedNotification;
use App\Notifications\Shift\ShiftReminderNotification;
use App\Notifications\Shift\ShiftsCancelledNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class ShiftService
{
    public function __construct(
        private TimesheetService $timesheetService
    ) {}

    public function createShift(array $data, User $createdBy): Shift
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $this->validateShiftCreation($data);

            $shift = Shift::create($data);

            ShiftCreated::dispatch($shift);

            $this->sendShiftScheduledNotifications($shift);

            Log::info('Shift created successfully', [
                'shift_id' => $shift->id,
                'assignment_id' => $shift->assignment_id,
                'start_time' => $shift->start_time,
                'created_by' => $createdBy->id,
            ]);

            return $shift->loadRelations();
        });
    }

    public function createShiftsForAssignment(Assignment $assignment, array $shiftsData, User $createdBy): array
    {
        return DB::transaction(function () use ($assignment, $shiftsData, $createdBy) {
            $createdShifts = [];

            foreach ($shiftsData as $shiftData) {
                $shiftData['assignment_id'] = $assignment->id;

                try {
                    $shift = $this->createShift($shiftData, $createdBy);
                    $createdShifts[] = $shift;
                } catch (\Exception $e) {
                    Log::warning('Failed to create shift', [
                        'assignment_id' => $assignment->id,
                        'shift_data' => $shiftData,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Bulk shifts created for assignment', [
                'assignment_id' => $assignment->id,
                'shifts_created' => count($createdShifts),
                'total_attempted' => count($shiftsData),
            ]);

            return $createdShifts;
        });
    }

    public function updateShift(Shift $shift, array $data): Shift
    {
        return DB::transaction(function () use ($shift, $data) {
            $originalStatus = $shift->status;

            $shift->update($data);

            if (isset($data['status']) && $shift->status !== $originalStatus) {
                ShiftStatusChanged::dispatch($shift, $originalStatus, $shift->status);
            }

            Log::info('Shift updated', [
                'shift_id' => $shift->id,
                'updated_fields' => array_keys($data),
            ]);

            return $shift->loadRelations();
        });
    }

    public function changeStatus(Shift $shift, string $status, ?string $reason = null): Shift
    {
        return DB::transaction(function () use ($shift, $status, $reason) {
            $originalStatus = $shift->status;
            $newStatus = ShiftStatus::from($status);

            $this->validateStatusTransition($shift, $newStatus);

            $shift->update([
                'status' => $newStatus,
                'notes' => $this->addStatusChangeNote($shift->notes, $originalStatus, $newStatus, $reason)
            ]);

            $this->dispatchStatusChangeEvents($shift, $originalStatus, $newStatus, $reason);

            $this->handleStatusSpecificLogic($shift, $newStatus, $reason);

            Log::info('Shift status changed', [
                'shift_id' => $shift->id,
                'from_status' => $originalStatus->value,
                'to_status' => $newStatus->value,
                'reason' => $reason,
            ]);

            return $shift->loadRelations();
        });
    }

    public function startShift(Shift $shift, ?string $notes = null): Shift
    {
        return $this->changeStatus($shift, ShiftStatus::IN_PROGRESS->value, $notes);
    }

    public function completeShift(Shift $shift, ?string $notes = null): Shift
    {
        $shift = $this->changeStatus($shift, ShiftStatus::COMPLETED->value, $notes);

        if (!$shift->timesheet) {
            $this->timesheetService->createTimesheetFromShift($shift);
        }

        return $shift;
    }

    public function cancelShift(Shift $shift, string $reason): Shift
    {
        return $this->changeStatus($shift, ShiftStatus::CANCELLED->value, $reason);
    }

    public function markAsNoShow(Shift $shift, string $reason): Shift
    {
        return $this->changeStatus($shift, ShiftStatus::NO_SHOW->value, $reason);
    }

    public function cancelFutureShifts(Assignment $assignment): void
    {
        $futureShifts = Shift::where('assignment_id', $assignment->id)
            ->where('start_time', '>', now())
            ->whereIn('status', [ShiftStatus::SCHEDULED, ShiftStatus::IN_PROGRESS])
            ->get();

        $cancelledCount = 0;

        foreach ($futureShifts as $shift) {
            try {
                $this->cancelShift($shift, 'Assignment cancelled');
                $cancelledCount++;
            } catch (\Exception $e) {
                Log::error('Failed to cancel shift', [
                    'shift_id' => $shift->id,
                    'assignment_id' => $assignment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($cancelledCount > 0) {
            ShiftsCancelled::dispatch($assignment, $futureShifts, 'Assignment cancelled');
        }

        Log::info('Future shifts cancelled for assignment', [
            'assignment_id' => $assignment->id,
            'shifts_cancelled' => $cancelledCount,
            'total_future_shifts' => $futureShifts->count(),
        ]);
    }

    public function suspendFutureShifts(Assignment $assignment): void
    {
        $futureShifts = Shift::where('assignment_id', $assignment->id)
            ->where('start_time', '>', now())
            ->where('status', ShiftStatus::SCHEDULED)
            ->get();

        foreach ($futureShifts as $shift) {
            try {
                $shift->update([
                    'notes' => $this->addSuspensionNote($shift->notes, 'Assignment suspended'),
                    'meta' => array_merge($shift->meta ?? [], ['suspended' => true])
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to suspend shift', [
                    'shift_id' => $shift->id,
                    'assignment_id' => $assignment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Future shifts suspended for assignment', [
            'assignment_id' => $assignment->id,
            'shifts_suspended' => $futureShifts->count(),
        ]);
    }

    public function reactivateSuspendedShifts(Assignment $assignment): void
    {
        $suspendedShifts = Shift::where('assignment_id', $assignment->id)
            ->where('start_time', '>', now())
            ->where('status', ShiftStatus::SCHEDULED)
            ->whereJsonContains('meta->suspended', true)
            ->get();

        foreach ($suspendedShifts as $shift) {
            try {
                $shift->update([
                    'notes' => $this->addReactivationNote($shift->notes, 'Assignment reactivated'),
                    'meta' => array_filter($shift->meta ?? [], function ($key) {
                        return $key !== 'suspended';
                    }, ARRAY_FILTER_USE_KEY)
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to reactivate shift', [
                    'shift_id' => $shift->id,
                    'assignment_id' => $assignment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Suspended shifts reactivated for assignment', [
            'assignment_id' => $assignment->id,
            'shifts_reactivated' => $suspendedShifts->count(),
        ]);
    }

    public function getFilteredShifts(array $filters): LengthAwarePaginator
    {
        $query = Shift::with([
            'assignment.agencyEmployee.employee.user',
            'assignment.contract.employer',
            'assignment.contract.agency',
            'location',
            'timesheet',
            'shiftApprovals.contact'
        ]);

        $this->applyFilters($query, $filters);

        $perPage = $filters['per_page'] ?? 20;

        return $query->orderBy('start_time')->paginate($perPage);
    }

    public function getShiftStats(User $user, array $filters = []): array
    {
        $query = $this->buildStatsQuery($user, $filters);

        $stats = [
            'total' => $query->count(),
            'scheduled' => (clone $query)->where('status', ShiftStatus::SCHEDULED)->count(),
            'in_progress' => (clone $query)->where('status', ShiftStatus::IN_PROGRESS)->count(),
            'completed' => (clone $query)->where('status', ShiftStatus::COMPLETED)->count(),
            'cancelled' => (clone $query)->where('status', ShiftStatus::CANCELLED)->count(),
            'no_show' => (clone $query)->where('status', ShiftStatus::NO_SHOW)->count(),
        ];

        $stats['performance'] = $this->getPerformanceMetrics($query);

        $stats['upcoming'] = (clone $query)->upcoming()->count();

        return $stats;
    }

    public function checkEmployeeShiftOverlaps($employeeId, $startTime, $endTime, $excludeShiftId = null): bool
    {
        return Shift::whereHas('assignment.agencyEmployee', function ($query) use ($employeeId) {
            $query->where('employee_id', $employeeId);
        })
            ->overlapping($startTime, $endTime, $excludeShiftId)
            ->exists();
    }

    public function sendShiftReminders(): void
    {
        $upcomingShifts = Shift::with(['assignment.agencyEmployee.employee.user', 'location'])
            ->where('start_time', '<=', now()->addHours(24))
            ->where('start_time', '>', now())
            ->where('status', ShiftStatus::SCHEDULED)
            ->get();

        foreach ($upcomingShifts as $shift) {
            try {
                $employeeUser = $shift->assignment->agencyEmployee->employee->user;
                $employeeUser->notify(new ShiftReminderNotification($shift));
            } catch (\Exception $e) {
                Log::error('Failed to send shift reminder', [
                    'shift_id' => $shift->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Shift reminders sent', [
            'shifts_count' => $upcomingShifts->count(),
        ]);
    }

    private function validateShiftCreation(array $data): void
    {
        $assignment = \App\Models\Assignment::findOrFail($data['assignment_id']);
        if (!$assignment->isActive()) {
            throw new \InvalidArgumentException('Shift requires an active assignment');
        }

        $startTime = Carbon::parse($data['start_time']);
        $endTime = Carbon::parse($data['end_time']);

        if ($this->checkEmployeeShiftOverlaps(
            $assignment->agencyEmployee->employee_id,
            $startTime,
            $endTime
        )) {
            throw new \InvalidArgumentException('Employee has overlapping shift');
        }

        $shiftDate = $data['shift_date'] ?? $startTime->toDateString();
        if ($shiftDate < $assignment->start_date) {
            throw new \InvalidArgumentException('Shift date is before assignment start date');
        }

        if ($assignment->end_date && $shiftDate > $assignment->end_date) {
            throw new \InvalidArgumentException('Shift date is after assignment end date');
        }

        $this->validateEmployeeAvailability($data);

        $durationHours = $startTime->diffInHours($endTime);
        if ($durationHours > 12) {
            throw new \InvalidArgumentException('Shift cannot exceed 12 hours');
        }

        if ($durationHours < 2) {
            throw new \InvalidArgumentException('Shift must be at least 2 hours');
        }
    }

    private function validateEmployeeAvailability(array $data): void
    {
        $assignment = \App\Models\Assignment::findOrFail($data['assignment_id']);
        $employeeId = $assignment->agencyEmployee->employee_id;
        $shiftDate = $data['shift_date'] ?? Carbon::parse($data['start_time'])->toDateString();
        $startTime = Carbon::parse($data['start_time']);
        $endTime = Carbon::parse($data['end_time']);

        $tempShift = new Shift([
            'assignment_id' => $data['assignment_id'],
            'shift_date' => $shiftDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        if (!$tempShift->isWithinEmployeeAvailability()) {
            throw new \InvalidArgumentException('Shift conflicts with employee availability or time off');
        }

        if (!$this->isWithinMaxWeeklyHours($employeeId, $shiftDate, $startTime, $endTime)) {
            throw new \InvalidArgumentException('Shift would exceed employee\'s maximum weekly hours');
        }
    }

    private function isWithinMaxWeeklyHours(int $employeeId, string $shiftDate, Carbon $startTime, Carbon $endTime): bool
    {
        $weekStart = Carbon::parse($shiftDate)->startOfWeek();
        $weekEnd = Carbon::parse($shiftDate)->endOfWeek();

        $maxWeeklyHours = \App\Models\AgencyEmployee::where('employee_id', $employeeId)
            ->where('status', 'active')
            ->value('max_weekly_hours');

        if (!$maxWeeklyHours) {
            return true;
        }

        $scheduledHours = Shift::whereHas('assignment.agencyEmployee', function ($query) use ($employeeId) {
            $query->where('employee_id', $employeeId);
        })
            ->whereBetween('shift_date', [$weekStart, $weekEnd])
            ->whereIn('status', [ShiftStatus::SCHEDULED, ShiftStatus::IN_PROGRESS])
            ->get()
            ->sum('duration_hours');

        $newShiftHours = $startTime->diffInHours($endTime);

        return ($scheduledHours + $newShiftHours) <= $maxWeeklyHours;
    }

    private function validateStatusTransition(Shift $shift, ShiftStatus $newStatus): void
    {
        $validTransitions = [
            ShiftStatus::SCHEDULED->value => [ShiftStatus::IN_PROGRESS, ShiftStatus::CANCELLED],
            ShiftStatus::IN_PROGRESS->value => [ShiftStatus::COMPLETED, ShiftStatus::CANCELLED, ShiftStatus::NO_SHOW],
            ShiftStatus::COMPLETED->value => [],
            ShiftStatus::CANCELLED->value => [],
            ShiftStatus::NO_SHOW->value => [],
        ];

        $currentStatus = $shift->status->value;

        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
            throw new \InvalidArgumentException(
                "Cannot transition shift from {$shift->status->label()} to {$newStatus->label()}"
            );
        }
    }

    private function dispatchStatusChangeEvents(
        Shift $shift,
        ShiftStatus $fromStatus,
        ShiftStatus $toStatus,
        ?string $reason
    ): void {
        ShiftStatusChanged::dispatch($shift, $fromStatus, $toStatus);

        match ($toStatus) {
            ShiftStatus::COMPLETED => ShiftCompleted::dispatch($shift),
            ShiftStatus::CANCELLED => ShiftCancelled::dispatch($shift, $reason),
            default => null
        };
    }

    private function handleStatusSpecificLogic(Shift $shift, ShiftStatus $newStatus, ?string $reason): void
    {
        match ($newStatus) {
            ShiftStatus::COMPLETED => $this->handleShiftCompletion($shift),
            ShiftStatus::CANCELLED => $this->handleShiftCancellation($shift, $reason),
            ShiftStatus::NO_SHOW => $this->handleNoShow($shift, $reason),
            default => null
        };
    }

    private function handleShiftCompletion(Shift $shift): void
    {
        Log::info('Shift completion handled', ['shift_id' => $shift->id]);
    }

    private function handleShiftCancellation(Shift $shift, ?string $reason): void
    {
        $this->sendShiftCancellationNotifications($shift, $reason);
    }

    private function handleNoShow(Shift $shift, string $reason): void
    {
        \App\Models\AuditLog::create([
            'action' => 'shift_no_show',
            'description' => "Employee no-show for shift {$shift->id}. Reason: {$reason}",
            'user_id' => auth()->id() ?? null,
            'target_type' => Shift::class,
            'target_id' => $shift->id,
            'metadata' => ['reason' => $reason]
        ]);

        $this->sendNoShowNotifications($shift, $reason);
    }

    private function sendShiftScheduledNotifications(Shift $shift): void
    {
        try {
            $employeeUser = $shift->assignment->agencyEmployee->employee->user;
            $employeeUser->notify(new ShiftScheduledNotification($shift));
        } catch (\Exception $e) {
            Log::error('Failed to send shift scheduled notification', [
                'shift_id' => $shift->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendShiftCancellationNotifications(Shift $shift, ?string $reason): void
    {
        try {
            $employeeUser = $shift->assignment->agencyEmployee->employee->user;
            $employeeUser->notify(new ShiftStatusChangedNotification(
                $shift,
                $shift->status,
                ShiftStatus::CANCELLED,
                $reason
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send shift cancellation notification', [
                'shift_id' => $shift->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendNoShowNotifications(Shift $shift, string $reason): void
    {
        $agencyUsers = User::whereHas('agent', function ($query) use ($shift) {
            $query->where('agency_id', $shift->assignment->agencyEmployee->agency_id);
        })->orWhereHas('agency', function ($query) use ($shift) {
            $query->where('id', $shift->assignment->agencyEmployee->agency_id);
        })->get();

        foreach ($agencyUsers as $user) {
            try {
                $user->notify(new ShiftStatusChangedNotification(
                    $shift,
                    $shift->status,
                    ShiftStatus::NO_SHOW,
                    $reason
                ));
            } catch (\Exception $e) {
                Log::error('Failed to send no-show notification', [
                    'shift_id' => $shift->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['assignment_id'])) {
            $query->where('assignment_id', $filters['assignment_id']);
        }

        if (isset($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (isset($filters['agency_id'])) {
            $query->whereHas('assignment.agencyEmployee', function ($q) use ($filters) {
                $q->where('agency_id', $filters['agency_id']);
            });
        }

        if (isset($filters['employer_id'])) {
            $query->whereHas('assignment.contract', function ($q) use ($filters) {
                $q->where('employer_id', $filters['employer_id']);
            });
        }

        if (isset($filters['employee_id'])) {
            $query->whereHas('assignment.agencyEmployee', function ($q) use ($filters) {
                $q->where('employee_id', $filters['employee_id']);
            });
        }

        if (isset($filters['start_date'])) {
            $query->where('shift_date', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('shift_date', '<=', $filters['end_date']);
        }

        if (isset($filters['search'])) {
            $query->whereHas('assignment', function ($q) use ($filters) {
                $q->where('role', 'like', '%' . $filters['search'] . '%');
            });
        }
    }

    private function buildStatsQuery(User $user, array $filters)
    {
        $query = Shift::query();

        if ($user->isAgency()) {
            $query->whereHas('assignment.agencyEmployee', function ($q) use ($user) {
                $q->where('agency_id', $user->getAgencyId());
            });
        } elseif ($user->isEmployer()) {
            $query->whereHas('assignment.contract', function ($q) use ($user) {
                $q->where('employer_id', $user->getEmployerId());
            });
        } elseif ($user->isEmployee()) {
            $query->whereHas('assignment.agencyEmployee.employee', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        if (isset($filters['start_date'])) {
            $query->where('shift_date', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->where('shift_date', '<=', $filters['end_date']);
        }

        return $query;
    }

    private function getPerformanceMetrics($query): array
    {
        $completedShifts = (clone $query)->where('status', ShiftStatus::COMPLETED)->count();
        $totalShifts = $query->count();
        $cancelledShifts = (clone $query)->where('status', ShiftStatus::CANCELLED)->count();

        return [
            'completion_rate' => $totalShifts > 0 ? ($completedShifts / $totalShifts) * 100 : 0,
            'cancellation_rate' => $totalShifts > 0 ? ($cancelledShifts / $totalShifts) * 100 : 0,
            'average_duration_hours' => (clone $query)->where('status', ShiftStatus::COMPLETED)->avg('duration_hours'),
        ];
    }

    private function addStatusChangeNote(
        ?string $existingNotes,
        ShiftStatus $from,
        ShiftStatus $to,
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

    private function addSuspensionNote(?string $existingNotes, string $reason): string
    {
        $note = "\n\n--- Shift Suspended ---\n";
        $note .= "Date: " . now()->format('Y-m-d H:i:s') . "\n";
        $note .= "Reason: {$reason}\n";

        return ($existingNotes ?? '') . $note;
    }

    private function addReactivationNote(?string $existingNotes, string $reason): string
    {
        $note = "\n\n--- Shift Reactivated ---\n";
        $note .= "Date: " . now()->format('Y-m-d H:i:s') . "\n";
        $note .= "Reason: {$reason}\n";

        return ($existingNotes ?? '') . $note;
    }
}
