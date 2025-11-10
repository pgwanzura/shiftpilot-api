<?php

namespace App\Services;

use App\Models\User;
use App\Models\Shift;
use App\Models\Placement;
use App\Models\TimeOffRequest;
use App\Models\EmployeeAvailability;
use App\Models\ShiftOffer;
use App\Models\Timesheet;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class CalendarEventsService
{
    public function getEventsForUser(User $user, array $filters): Collection
    {
        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];
        $entityType = $filters['filter'] ?? 'all';

        $events = match ($user->role) {
            'super_admin' => $this->getAllEvents($startDate, $endDate, $entityType),
            'agency_admin', 'agent' => $this->getAgencyEvents($user, $startDate, $endDate, $entityType),
            'employer_admin', 'contact' => $this->getEmployerEvents($user, $startDate, $endDate, $entityType),
            'employee' => $this->getEmployeeEvents($user, $startDate, $endDate, $entityType),
            default => collect()
        };

        return $this->applyFilters($events, $filters);
    }

    public function getUpcomingShifts(User $user): Collection
    {
        $today = now()->toDateString();
        $nextWeek = now()->addWeek()->toDateString();

        return match ($user->role) {
            'employee' => Shift::with(['employer', 'location'])
                ->where('employee_id', $user->employee?->id)
                ->whereBetween('start_time', [$today, $nextWeek])
                ->whereIn('status', ['assigned', 'offered'])
                ->orderBy('start_time')
                ->limit(10)
                ->get(),

            'agency_admin', 'agent' => Shift::with(['employer', 'employee', 'location'])
                ->where('agency_id', $user->agency?->id)
                ->whereBetween('start_time', [$today, $nextWeek])
                ->whereIn('status', ['assigned', 'offered'])
                ->orderBy('start_time')
                ->limit(10)
                ->get(),

            'employer_admin', 'contact' => Shift::with(['agency', 'employee', 'location'])
                ->where('employer_id', $user->employer?->id)
                ->whereBetween('start_time', [$today, $nextWeek])
                ->whereIn('status', ['assigned', 'offered'])
                ->orderBy('start_time')
                ->limit(10)
                ->get(),

            default => collect()
        };
    }

    public function getPendingActions(User $user): Collection
    {
        $events = $this->getEventsForUser($user, [
            'start_date' => now()->subDays(7)->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'requires_action' => true
        ]);

        return $events->filter(function ($event) use ($user) {
            return $event->requires_action &&
                in_array($user->role, $event->actionable_by);
        });
    }

    public function getUrgentShifts(User $user): Collection
    {
        $events = $this->getEventsForUser($user, [
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString()
        ]);

        return $events->filter(function ($event) {
            return $event->priority === 'urgent' ||
                ($event->status === 'open' && $event->start_time->diffInHours(now()) < 24);
        });
    }

    public function getEventStats(User $user): array
    {
        $today = now()->toDateString();
        $nextWeek = now()->addWeek()->toDateString();

        return match ($user->role) {
            'employee' => $this->getEmployeeStats($user, $today, $nextWeek),
            'agency_admin', 'agent' => $this->getAgencyStats($user, $today, $nextWeek),
            'employer_admin', 'contact' => $this->getEmployerStats($user, $today, $nextWeek),
            default => $this->getDefaultStats()
        };
    }

    public function getWorkloadOverview(User $user): array
    {
        $startDate = now()->startOfWeek();
        $endDate = now()->addWeeks(2)->endOfWeek();

        $events = $this->getEventsForUser($user, [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString()
        ]);

        $workload = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dayEvents = $events->filter(function ($event) use ($date) {
                return Carbon::parse($event->date)->isSameDay($date);
            });

            $workload[$date->toDateString()] = [
                'total_events' => $dayEvents->count(),
                'shifts' => $dayEvents->where('entity_type', 'shift')->count(),
                'placements' => $dayEvents->where('entity_type', 'placement')->count(),
                'urgent' => $dayEvents->where('priority', 'urgent')->count(),
                'requires_action' => $dayEvents->where('requires_action', true)->count(),
            ];
        }

        return $workload;
    }

    public function getFilterOptions(User $user): array
    {
        $options = [
            'status' => ['open', 'offered', 'assigned', 'completed', 'agency_approved', 'employer_approved', 'billed', 'cancelled'],
            'priority' => ['low', 'medium', 'high', 'urgent'],
            'entity_type' => ['shift', 'placement', 'time_off', 'availability', 'interview'],
            'view' => ['month', 'week', 'day', 'schedule', 'placement'],
        ];

        if (in_array($user->role, ['agency_admin', 'agent'])) {
            $options['employers'] = $this->getUserEmployers($user);
            $options['locations'] = $this->getUserLocations($user);
        }

        if (in_array($user->role, ['employer_admin', 'contact'])) {
            $options['agencies'] = $this->getUserAgencies($user);
        }

        return $options;
    }

    public function executeEventAction(User $user, string $eventId, array $data): array
    {
        $event = $this->findEventById($eventId);

        if (!$event) {
            throw new \Exception('Event not found');
        }

        if (!in_array($user->role, $event->actionable_by)) {
            throw new \Exception('User not authorized to perform this action');
        }

        $actionType = $data['action_type'];

        return match ($actionType) {
            'accept' => $this->acceptShiftOffer($user, $event, $data),
            'reject' => $this->rejectShiftOffer($user, $event, $data),
            'approve' => $this->approveTimeOff($user, $event, $data),
            'complete' => $this->completeShift($user, $event, $data),
            default => throw new \Exception('Invalid action type')
        };
    }

    public function offerShiftToEmployee(User $user, string $shiftId, array $data): array
    {
        $shift = Shift::findOrFail($shiftId);

        if (!$this->canManageShift($user, $shift)) {
            throw new \Exception('Not authorized to offer this shift');
        }

        $shiftOffer = ShiftOffer::create([
            'shift_id' => $shift->id,
            'employee_id' => $data['employee_id'],
            'offered_by_id' => $user->id,
            'status' => 'pending',
            'expires_at' => $data['expires_at'] ?? now()->addHours(24),
        ]);

        $shift->update(['status' => 'offered']);

        return [
            'shift_offer' => $shiftOffer,
            'message' => 'Shift offered successfully'
        ];
    }

    public function assignShiftToEmployee(User $user, string $shiftId, array $data): array
    {
        $shift = Shift::findOrFail($shiftId);

        if (!$this->canManageShift($user, $shift)) {
            throw new \Exception('Not authorized to assign this shift');
        }

        $shift->update([
            'employee_id' => $data['employee_id'],
            'status' => 'assigned'
        ]);

        return [
            'shift' => $shift,
            'message' => 'Shift assigned successfully'
        ];
    }

    public function completeShift(User $user, string $shiftId, array $data): array
    {
        $shift = Shift::findOrFail($shiftId);

        if (!$this->canCompleteShift($user, $shift)) {
            throw new \Exception('Not authorized to complete this shift');
        }

        $shift->update(['status' => 'completed']);

        $timesheet = Timesheet::create([
            'shift_id' => $shift->id,
            'employee_id' => $shift->employee_id,
            'status' => 'pending',
            'notes' => $data['notes'] ?? 'Shift completed'
        ]);

        return [
            'shift' => $shift,
            'timesheet' => $timesheet,
            'message' => 'Shift completed successfully'
        ];
    }

    public function approveShift(User $user, string $shiftId, array $data): array
    {
        $shift = Shift::findOrFail($shiftId);
        $approvalType = $data['approval_type'];

        if (!$this->canApproveShift($user, $shift, $approvalType)) {
            throw new \Exception('Not authorized to approve this shift');
        }

        $newStatus = $approvalType === 'agency' ? 'agency_approved' : 'employer_approved';
        $shift->update(['status' => $newStatus]);

        return [
            'shift' => $shift,
            'message' => 'Shift approved successfully'
        ];
    }

    public function clockIn(User $user, string $shiftId, array $data): array
    {
        $shift = Shift::findOrFail($shiftId);

        if (!$this->canClockIn($user, $shift)) {
            throw new \Exception('Not authorized to clock in for this shift');
        }

        $timesheet = Timesheet::updateOrCreate(
            ['shift_id' => $shift->id],
            [
                'employee_id' => $shift->employee_id,
                'clock_in' => now(),
                'status' => 'in_progress'
            ]
        );

        return [
            'timesheet' => $timesheet,
            'message' => 'Clocked in successfully'
        ];
    }

    public function clockOut(User $user, string $shiftId, array $data): array
    {
        $shift = Shift::findOrFail($shiftId);
        $timesheet = Timesheet::where('shift_id', $shift->id)->first();

        if (!$timesheet || !$this->canClockOut($user, $shift)) {
            throw new \Exception('Not authorized to clock out for this shift');
        }

        $timesheet->update([
            'clock_out' => now(),
            'break_minutes' => $data['break_minutes'] ?? 0,
            'status' => 'pending'
        ]);

        $hoursWorked = $timesheet->clock_out->diffInMinutes($timesheet->clock_in) / 60;
        $hoursWorked -= ($timesheet->break_minutes / 60);
        $timesheet->update(['hours_worked' => $hoursWorked]);

        return [
            'timesheet' => $timesheet,
            'message' => 'Clocked out successfully'
        ];
    }

    public function getEmployeeAvailability(User $user, array $filters): Collection
    {
        $employeeId = $user->employee?->id;

        if (!$employeeId) {
            return collect();
        }

        $query = EmployeeAvailability::where('employee_id', $employeeId);

        if (isset($filters['start_date'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereDate('start_date', '>=', $filters['start_date'])
                    ->orWhereNull('start_date');
            });
        }

        if (isset($filters['end_date'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereDate('end_date', '<=', $filters['end_date'])
                    ->orWhereNull('end_date');
            });
        }

        return $query->get();
    }

    public function updateEmployeeAvailability(User $user, array $data): array
    {
        $employeeId = $user->employee?->id;

        if (!$employeeId) {
            throw new \Exception('User is not an employee');
        }

        EmployeeAvailability::where('employee_id', $employeeId)->delete();

        foreach ($data['availabilities'] as $availability) {
            EmployeeAvailability::create(array_merge($availability, ['employee_id' => $employeeId]));
        }

        return [
            'message' => 'Availability updated successfully',
            'availabilities' => $this->getEmployeeAvailability($user, [])
        ];
    }

    public function requestTimeOff(User $user, array $data): array
    {
        $employeeId = $user->employee?->id;

        if (!$employeeId) {
            throw new \Exception('User is not an employee');
        }

        $timeOffRequest = TimeOffRequest::create(array_merge($data, [
            'employee_id' => $employeeId,
            'status' => 'pending'
        ]));

        return [
            'time_off_request' => $timeOffRequest,
            'message' => 'Time off request submitted successfully'
        ];
    }

    public function bulkOfferShifts(User $user, array $data): array
    {
        $results = [];

        foreach ($data['shift_ids'] as $shiftId) {
            foreach ($data['employee_ids'] as $employeeId) {
                try {
                    $result = $this->offerShiftToEmployee($user, $shiftId, [
                        'employee_id' => $employeeId,
                        'expires_at' => $data['expires_at'] ?? null
                    ]);
                    $results[] = $result;
                } catch (\Exception $e) {
                    $results[] = [
                        'shift_id' => $shiftId,
                        'employee_id' => $employeeId,
                        'error' => $e->getMessage()
                    ];
                }
            }
        }

        return [
            'results' => $results,
            'message' => 'Bulk shift offering completed'
        ];
    }

    public function bulkAssignShifts(User $user, array $data): array
    {
        $results = [];

        foreach ($data['shift_ids'] as $shiftId) {
            foreach ($data['employee_ids'] as $employeeId) {
                try {
                    $result = $this->assignShiftToEmployee($user, $shiftId, [
                        'employee_id' => $employeeId
                    ]);
                    $results[] = $result;
                } catch (\Exception $e) {
                    $results[] = [
                        'shift_id' => $shiftId,
                        'employee_id' => $employeeId,
                        'error' => $e->getMessage()
                    ];
                }
            }
        }

        return [
            'results' => $results,
            'message' => 'Bulk shift assignment completed'
        ];
    }

    public function exportEvents(User $user, array $data): array
    {
        $events = $this->getEventsForUser($user, $data);

        $format = $data['format'] ?? 'csv';
        $filename = 'calendar-export-' . now()->format('Y-m-d-H-i-s') . '.' . $format;

        $exportData = $events->map(function ($event) {
            return [
                'Title' => $event->title,
                'Date' => $event->date,
                'Start Time' => $event->startTime,
                'End Time' => $event->endTime,
                'Type' => $event->type,
                'Status' => $event->status,
                'Priority' => $event->priority,
                'Location' => $event->locationName,
                'Employer' => $event->employerName,
                'Employee' => $event->employeeName,
            ];
        });

        return [
            'filename' => $filename,
            'format' => $format,
            'data' => $exportData,
            'count' => $exportData->count()
        ];
    }

    public function generatePrintableSchedule(User $user, array $data): array
    {
        $events = $this->getEventsForUser($user, $data);

        $scheduleData = [
            'user' => $user->name,
            'period' => $data['start_date'] . ' to ' . $data['end_date'],
            'generated_at' => now()->toDateTimeString(),
            'events' => $events->groupBy('date')->map(function ($dayEvents, $date) {
                return [
                    'date' => $date,
                    'events' => $dayEvents->map(function ($event) {
                        return [
                            'time' => $event->startTime . ' - ' . $event->endTime,
                            'title' => $event->title,
                            'type' => $event->type,
                            'location' => $event->locationName,
                            'status' => $event->status
                        ];
                    })->toArray()
                ];
            })->values()->toArray()
        ];

        return $scheduleData;
    }

    // Private helper methods

    private function getAllEvents(string $startDate, string $endDate, string $entityType): Collection
    {
        $events = collect();

        if ($this->shouldIncludeEntity($entityType, 'shifts')) {
            $events = $events->merge($this->getShifts($startDate, $endDate));
        }

        if ($this->shouldIncludeEntity($entityType, 'placements')) {
            $events = $events->merge($this->getPlacements($startDate, $endDate));
        }

        if ($this->shouldIncludeEntity($entityType, 'time_off')) {
            $events = $events->merge($this->getTimeOffRequests($startDate, $endDate));
        }

        if ($this->shouldIncludeEntity($entityType, 'interviews')) {
            $events = $events->merge($this->getInterviews($startDate, $endDate));
        }

        if ($this->shouldIncludeEntity($entityType, 'availabilities')) {
            $events = $events->merge($this->getAvailabilities($startDate, $endDate));
        }

        return $events;
    }

    private function getAgencyEvents(User $user, string $startDate, string $endDate, string $entityType): Collection
    {
        $agencyId = $user->agency?->id;

        if (!$agencyId) {
            return collect();
        }

        $events = collect();

        if ($this->shouldIncludeEntity($entityType, 'shifts')) {
            $shifts = Shift::with(['employer', 'employee', 'location'])
                ->where('agency_id', $agencyId)
                ->whereBetween('start_time', [$startDate, $endDate])
                ->get();
            $events = $events->merge($shifts);
        }

        if ($this->shouldIncludeEntity($entityType, 'placements')) {
            $placements = Placement::with(['employer', 'location'])
                ->where('selected_agency_id', $agencyId)
                ->whereBetweenDates($startDate, $endDate)
                ->get();
            $events = $events->merge($placements);
        }

        if ($this->shouldIncludeEntity($entityType, 'time_off')) {
            $timeOff = TimeOffRequest::with(['employee'])
                ->whereHas('employee', fn($query) => $query->where('agency_id', $agencyId))
                ->whereBetweenDates($startDate, $endDate)
                ->get();
            $events = $events->merge($timeOff);
        }

        if ($this->shouldIncludeEntity($entityType, 'availabilities')) {
            $availabilities = EmployeeAvailability::with(['employee'])
                ->whereHas('employee', fn($query) => $query->where('agency_id', $agencyId))
                ->whereOverlappingDates($startDate, $endDate)
                ->get();
            $events = $events->merge($availabilities);
        }

        return $events;
    }

    private function getEmployerEvents(User $user, string $startDate, string $endDate, string $entityType): Collection
    {
        $employerId = $user->employer?->id;

        if (!$employerId) {
            return collect();
        }

        $events = collect();

        if ($this->shouldIncludeEntity($entityType, 'shifts')) {
            $shifts = Shift::with(['agency', 'employee', 'location'])
                ->where('employer_id', $employerId)
                ->whereBetween('start_time', [$startDate, $endDate])
                ->get();
            $events = $events->merge($shifts);
        }

        if ($this->shouldIncludeEntity($entityType, 'placements')) {
            $placements = Placement::with(['selectedAgency', 'selectedEmployee', 'location'])
                ->where('employer_id', $employerId)
                ->whereBetweenDates($startDate, $endDate)
                ->get();
            $events = $events->merge($placements);
        }

        if ($this->shouldIncludeEntity($entityType, 'time_off')) {
            $timeOff = TimeOffRequest::with(['employee'])
                ->whereHas('employee', fn($query) => $query->where('employer_id', $employerId))
                ->whereBetweenDates($startDate, $endDate)
                ->get();
            $events = $events->merge($timeOff);
        }

        return $events;
    }

    private function getEmployeeEvents(User $user, string $startDate, string $endDate, string $entityType): Collection
    {
        $employee = $user->employee;

        if (!$employee) {
            return collect();
        }

        $events = collect();

        if ($this->shouldIncludeEntity($entityType, 'shifts')) {
            $shifts = Shift::with(['employer', 'location'])
                ->where('employee_id', $employee->id)
                ->whereBetween('start_time', [$startDate, $endDate])
                ->get();
            $events = $events->merge($shifts);
        }

        if ($this->shouldIncludeEntity($entityType, 'time_off')) {
            $timeOff = TimeOffRequest::where('employee_id', $employee->id)
                ->whereBetweenDates($startDate, $endDate)
                ->get();
            $events = $events->merge($timeOff);
        }

        if ($this->shouldIncludeEntity($entityType, 'availabilities')) {
            $availabilities = EmployeeAvailability::where('employee_id', $employee->id)
                ->whereOverlappingDates($startDate, $endDate)
                ->get();
            $events = $events->merge($availabilities);
        }

        if ($this->shouldIncludeEntity($entityType, 'interviews')) {
            $offers = ShiftOffer::with(['shift.employer', 'shift.location'])
                ->where('employee_id', $employee->id)
                ->whereHas('shift', fn($query) => $query->whereBetween('start_time', [$startDate, $endDate]))
                ->get();
            $events = $events->merge($offers);
        }

        return $events;
    }

    private function getShifts(string $startDate, string $endDate): Collection
    {
        return Shift::with(['employer', 'agency', 'employee', 'location'])
            ->whereBetween('start_time', [$startDate, $endDate])
            ->get();
    }

    private function getPlacements(string $startDate, string $endDate): Collection
    {
        return Placement::with(['employer', 'selectedAgency', 'selectedEmployee', 'location'])
            ->whereBetweenDates($startDate, $endDate)
            ->get();
    }

    private function getTimeOffRequests(string $startDate, string $endDate): Collection
    {
        return TimeOffRequest::with(['employee.user'])
            ->whereBetweenDates($startDate, $endDate)
            ->get();
    }

    private function getInterviews(string $startDate, string $endDate): Collection
    {
        return ShiftOffer::with(['shift.employer', 'employee.user'])
            ->whereHas('shift', fn($query) => $query->whereBetween('start_time', [$startDate, $endDate]))
            ->get();
    }

    private function getAvailabilities(string $startDate, string $endDate): Collection
    {
        return EmployeeAvailability::with(['employee.user'])
            ->whereOverlappingDates($startDate, $endDate)
            ->get();
    }

    private function applyFilters(Collection $events, array $filters): Collection
    {
        if (isset($filters['status']) && is_array($filters['status'])) {
            $events = $events->filter(fn($event) => in_array($event->status, $filters['status']));
        }

        if (isset($filters['entity_type']) && $filters['entity_type'] !== 'all') {
            $events = $events->filter(fn($event) => $this->getEntityType($event) === $filters['entity_type']);
        }

        if (isset($filters['employer_id'])) {
            $events = $events->filter(fn($event) => $event->employer_id == $filters['employer_id']);
        }

        if (isset($filters['agency_id'])) {
            $events = $events->filter(fn($event) => isset($event->agency_id) && $event->agency_id == $filters['agency_id']);
        }

        if (isset($filters['location_id'])) {
            $events = $events->filter(fn($event) => isset($event->location_id) && $event->location_id == $filters['location_id']);
        }

        if (isset($filters['requires_action'])) {
            $events = $events->filter(fn($event) => $event->requires_action == $filters['requires_action']);
        }

        return $events;
    }

    private function getEmployeeStats(User $user, string $today, string $nextWeek): array
    {
        $employeeId = $user->employee?->id;

        return [
            'total' => Shift::where('employee_id', $employeeId)->count(),
            'shifts' => Shift::where('employee_id', $employeeId)->count(),
            'upcoming' => Shift::where('employee_id', $employeeId)
                ->where('start_time', '>=', $today)
                ->whereIn('status', ['assigned', 'offered'])
                ->count(),
            'this_week' => Shift::where('employee_id', $employeeId)
                ->whereBetween('start_time', [$today, $nextWeek])
                ->whereIn('status', ['assigned', 'offered'])
                ->count(),
            'time_off' => TimeOffRequest::where('employee_id', $employeeId)
                ->where('status', 'approved')
                ->where('end_date', '>=', $today)
                ->count(),
        ];
    }

    private function getAgencyStats(User $user, string $today, string $nextWeek): array
    {
        $agencyId = $user->agency?->id;

        return [
            'total' => Shift::where('agency_id', $agencyId)->count(),
            'shifts' => Shift::where('agency_id', $agencyId)->count(),
            'placements' => Placement::where('selected_agency_id', $agencyId)->count(),
            'upcoming' => Shift::where('agency_id', $agencyId)
                ->where('start_time', '>=', $today)
                ->whereIn('status', ['assigned', 'offered'])
                ->count(),
            'this_week' => Shift::where('agency_id', $agencyId)
                ->whereBetween('start_time', [$today, $nextWeek])
                ->whereIn('status', ['assigned', 'offered'])
                ->count(),
            'time_off' => TimeOffRequest::whereHas('employee', fn($q) => $q->where('agency_id', $agencyId))
                ->where('status', 'approved')
                ->count(),
        ];
    }

    private function getEmployerStats(User $user, string $today, string $nextWeek): array
    {
        $employerId = $user->employer?->id;

        return [
            'total' => Shift::where('employer_id', $employerId)->count(),
            'shifts' => Shift::where('employer_id', $employerId)->count(),
            'placements' => Placement::where('employer_id', $employerId)->count(),
            'upcoming' => Shift::where('employer_id', $employerId)
                ->where('start_time', '>=', $today)
                ->whereIn('status', ['assigned', 'offered'])
                ->count(),
            'this_week' => Shift::where('employer_id', $employerId)
                ->whereBetween('start_time', [$today, $nextWeek])
                ->whereIn('status', ['assigned', 'offered'])
                ->count(),
        ];
    }

    private function getDefaultStats(): array
    {
        return [
            'total' => 0,
            'shifts' => 0,
            'placements' => 0,
            'upcoming' => 0,
            'this_week' => 0,
            'time_off' => 0,
        ];
    }

    private function shouldIncludeEntity(string $entityType, string $targetEntity): bool
    {
        return $entityType === 'all' || $entityType === $targetEntity;
    }

    private function getEntityType($model): string
    {
        return match (get_class($model)) {
            Shift::class => 'shift',
            Placement::class => 'placement',
            TimeOffRequest::class => 'time_off',
            EmployeeAvailability::class => 'availability',
            ShiftOffer::class => 'interview',
            default => 'shift'
        };
    }

    private function findEventById(string $eventId)
    {
        $parts = explode('-', $eventId);
        if (count($parts) < 2) {
            return null;
        }

        $modelClass = 'App\\Models\\' . str_replace('_', '', ucwords($parts[0], '_'));
        $id = $parts[1];

        if (!class_exists($modelClass)) {
            return null;
        }

        return $modelClass::find($id);
    }

    private function canManageShift(User $user, Shift $shift): bool
    {
        return in_array($user->role, ['agency_admin', 'agent']) &&
            $shift->agency_id === $user->agency?->id;
    }

    private function canCompleteShift(User $user, Shift $shift): bool
    {
        return $user->role === 'employee' &&
            $shift->employee_id === $user->employee?->id;
    }

    private function canApproveShift(User $user, Shift $shift, string $approvalType): bool
    {
        if ($approvalType === 'agency') {
            return in_array($user->role, ['agency_admin', 'agent']) &&
                $shift->agency_id === $user->agency?->id;
        } else {
            return in_array($user->role, ['employer_admin', 'contact']) &&
                $shift->employer_id === $user->employer?->id;
        }
    }

    private function canClockIn(User $user, Shift $shift): bool
    {
        return $user->role === 'employee' &&
            $shift->employee_id === $user->employee?->id &&
            $shift->status === 'assigned';
    }

    private function canClockOut(User $user, Shift $shift): bool
    {
        return $this->canClockIn($user, $shift);
    }

    private function getUserEmployers(User $user): array
    {
        return \App\Models\Employer::whereHas('agencies', function ($query) use ($user) {
            $query->where('agencies.id', $user->agency?->id);
        })->get()->toArray();
    }

    private function getUserLocations(User $user): array
    {
        return \App\Models\Location::whereHas('employer.agencies', function ($query) use ($user) {
            $query->where('agencies.id', $user->agency?->id);
        })->get()->toArray();
    }

    private function getUserAgencies(User $user): array
    {
        return \App\Models\Agency::whereHas('employers', function ($query) use ($user) {
            $query->where('employers.id', $user->employer?->id);
        })->get()->toArray();
    }

    private function acceptShiftOffer(User $user, $event, array $data): array
    {
        $event->update(['status' => 'accepted']);
        $event->shift->update(['status' => 'assigned', 'employee_id' => $user->employee?->id]);

        return [
            'message' => 'Shift offer accepted successfully',
            'event' => $event
        ];
    }

    private function rejectShiftOffer(User $user, $event, array $data): array
    {
        $event->update(['status' => 'rejected']);

        return [
            'message' => 'Shift offer rejected successfully',
            'event' => $event
        ];
    }

    private function approveTimeOff(User $user, $event, array $data): array
    {
        $event->update([
            'status' => 'approved',
            'approved_by_id' => $user->id,
            'approved_at' => now()
        ]);

        return [
            'message' => 'Time off request approved successfully',
            'event' => $event
        ];
    }
}
