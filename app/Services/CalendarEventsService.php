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
use Illuminate\Support\Facades\DB;

class CalendarEventsService
{
    public function getEventsForUser(User $user, array $filters): Collection
    {
        $query = $this->buildEventQuery($user, $filters);
        return $query->get();
    }

    public function getUpcomingShifts(User $user): Collection
    {
        return Shift::with($this->getShiftRelations($user))
            ->whereBetween('start_time', [now()->toDateString(), now()->addWeek()->toDateString()])
            ->whereIn('status', ['assigned', 'offered'])
            ->orderBy('start_time')
            ->limit(10)
            ->get();
    }

    public function getUrgentShifts(User $user): Collection
    {
        return $this->getEventsForUser($user, [
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString()
        ])->filter(function ($event) {
            return $event->priority === 'urgent' ||
                ($event->status === 'open' && $event->start_time->diffInHours(now()) < 24);
        });
    }

    public function getPendingActions(User $user): Collection
    {
        return $this->getEventsForUser($user, [
            'start_date' => now()->subDays(7)->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'requires_action' => true
        ])->filter(fn($event) => $event->requires_action && in_array($user->role, $event->actionable_by));
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
            $dayEvents = $events->filter(fn($event) => Carbon::parse($event->date)->isSameDay($date));

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
            default => throw new \Exception('Invalid action type')
        };
    }

    public function offerShiftToEmployee(User $user, string $shiftId, array $data): array
    {
        $shift = Shift::findOrFail($shiftId);

        $this->authorizeShiftManagement($user, $shift);

        return DB::transaction(function () use ($user, $shift, $data) {
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
        });
    }

    public function assignShiftToEmployee(User $user, string $shiftId, array $data): array
    {
        $shift = Shift::findOrFail($shiftId);

        $this->authorizeShiftManagement($user, $shift);

        $shift->update([
            'employee_id' => $data['employee_id'],
            'status' => 'assigned'
        ]);

        return [
            'shift' => $shift->load('employer', 'employee', 'location'),
            'message' => 'Shift assigned successfully'
        ];
    }

    public function completeShift(User $user, string $shiftId, array $data): array
    {
        $shift = Shift::findOrFail($shiftId);

        $this->authorizeShiftCompletion($user, $shift);

        return DB::transaction(function () use ($shift, $data) {
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
        });
    }

    public function approveShift(User $user, string $shiftId, array $data): array
    {
        $shift = Shift::findOrFail($shiftId);
        $approvalType = $data['approval_type'];

        $this->authorizeShiftApproval($user, $shift, $approvalType);

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

        $this->authorizeClockIn($user, $shift);

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
        $timesheet = Timesheet::where('shift_id', $shift->id)->firstOrFail();

        $this->authorizeClockOut($user, $shift);

        return DB::transaction(function () use ($timesheet, $data) {
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
        });
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

        return DB::transaction(function () use ($employeeId, $data) {
            EmployeeAvailability::where('employee_id', $employeeId)->delete();

            $availabilities = collect($data['availabilities'])->map(function ($availability) use ($employeeId) {
                return EmployeeAvailability::create(array_merge($availability, ['employee_id' => $employeeId]));
            });

            return [
                'message' => 'Availability updated successfully',
                'availabilities' => $availabilities
            ];
        });
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
            'time_off_request' => $timeOffRequest->load('employee'),
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

    private function buildEventQuery(User $user, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];
        $entityType = $filters['filter'] ?? 'all';

        return match ($user->role) {
            'super_admin' => $this->buildSuperAdminQuery($startDate, $endDate, $entityType),
            'agency_admin', 'agent' => $this->buildAgencyQuery($user, $startDate, $endDate, $entityType),
            'employer_admin', 'contact' => $this->buildEmployerQuery($user, $startDate, $endDate, $entityType),
            'employee' => $this->buildEmployeeQuery($user, $startDate, $endDate, $entityType),
            default => Shift::whereNull('id')
        };
    }

    private function buildSuperAdminQuery(string $startDate, string $endDate, string $entityType): \Illuminate\Database\Eloquent\Builder
    {
        $baseQuery = Shift::whereNull('id');

        if ($this->shouldIncludeEntity($entityType, 'shifts')) {
            $shifts = Shift::with(['employer', 'agency', 'employee', 'location'])
                ->whereBetween('start_time', [$startDate, $endDate]);
            $baseQuery = $shifts;
        }

        if ($this->shouldIncludeEntity($entityType, 'placements')) {
            $placements = Placement::with(['employer', 'selectedAgency', 'selectedEmployee', 'location'])
                ->whereBetweenDates($startDate, $endDate);
            $baseQuery = $baseQuery->getQuery() !== Shift::whereNull('id')->getQuery()
                ? $baseQuery->union($placements)
                : $placements;
        }

        return $baseQuery;
    }

    private function buildAgencyQuery(User $user, string $startDate, string $endDate, string $entityType): \Illuminate\Database\Eloquent\Builder
    {
        $agencyId = $user->agency?->id;
        $baseQuery = Shift::whereNull('id');

        if (!$agencyId) {
            return $baseQuery;
        }

        if ($this->shouldIncludeEntity($entityType, 'shifts')) {
            $shifts = Shift::with(['employer', 'employee', 'location'])
                ->where('agency_id', $agencyId)
                ->whereBetween('start_time', [$startDate, $endDate]);
            $baseQuery = $shifts;
        }

        if ($this->shouldIncludeEntity($entityType, 'placements')) {
            $placements = Placement::with(['employer', 'location'])
                ->where('selected_agency_id', $agencyId)
                ->whereBetweenDates($startDate, $endDate);
            $baseQuery = $baseQuery->getQuery() !== Shift::whereNull('id')->getQuery()
                ? $baseQuery->union($placements)
                : $placements;
        }

        return $baseQuery;
    }

    private function buildEmployerQuery(User $user, string $startDate, string $endDate, string $entityType): \Illuminate\Database\Eloquent\Builder
    {
        $employerId = $user->employer?->id;
        $baseQuery = Shift::whereNull('id');

        if (!$employerId) {
            return $baseQuery;
        }

        if ($this->shouldIncludeEntity($entityType, 'shifts')) {
            $shifts = Shift::with(['agency', 'employee', 'location'])
                ->where('employer_id', $employerId)
                ->whereBetween('start_time', [$startDate, $endDate]);
            $baseQuery = $shifts;
        }

        if ($this->shouldIncludeEntity($entityType, 'placements')) {
            $placements = Placement::with(['selectedAgency', 'selectedEmployee', 'location'])
                ->where('employer_id', $employerId)
                ->whereBetweenDates($startDate, $endDate);
            $baseQuery = $baseQuery->union($placements);
        }

        return $baseQuery;
    }

    private function buildEmployeeQuery(User $user, string $startDate, string $endDate, string $entityType): \Illuminate\Database\Eloquent\Builder
    {
        $employee = $user->employee;
        $baseQuery = Shift::whereNull('id');

        if (!$employee) {
            return $baseQuery;
        }

        if ($this->shouldIncludeEntity($entityType, 'shifts')) {
            $shifts = Shift::with(['employer', 'location'])
                ->where('employee_id', $employee->id)
                ->whereBetween('start_time', [$startDate, $endDate]);
            $baseQuery = $shifts;
        }

        if ($this->shouldIncludeEntity($entityType, 'time_off')) {
            $timeOff = TimeOffRequest::where('employee_id', $employee->id)
                ->whereBetweenDates($startDate, $endDate);
            $baseQuery = $baseQuery->union($timeOff);
        }

        return $baseQuery;
    }

    private function getEmployeeStats(User $user, string $today, string $nextWeek): array
    {
        $employeeId = $user->employee?->id;

        if (!$employeeId) {
            return $this->getDefaultStats();
        }

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

        if (!$agencyId) {
            return $this->getDefaultStats();
        }

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

        if (!$employerId) {
            return $this->getDefaultStats();
        }

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

    private function authorizeShiftManagement(User $user, Shift $shift): void
    {
        if (!in_array($user->role, ['agency_admin', 'agent']) || $shift->agency_id !== $user->agency?->id) {
            throw new \Exception('Not authorized to manage this shift');
        }
    }

    private function authorizeShiftCompletion(User $user, Shift $shift): void
    {
        if ($user->role !== 'employee' || $shift->employee_id !== $user->employee?->id) {
            throw new \Exception('Not authorized to complete this shift');
        }
    }

    private function authorizeShiftApproval(User $user, Shift $shift, string $approvalType): void
    {
        if ($approvalType === 'agency') {
            if (!in_array($user->role, ['agency_admin', 'agent']) || $shift->agency_id !== $user->agency?->id) {
                throw new \Exception('Not authorized to approve this shift for agency');
            }
        } else {
            if (!in_array($user->role, ['employer_admin', 'contact']) || $shift->employer_id !== $user->employer?->id) {
                throw new \Exception('Not authorized to approve this shift for employer');
            }
        }
    }

    private function authorizeClockIn(User $user, Shift $shift): void
    {
        if ($user->role !== 'employee' || $shift->employee_id !== $user->employee?->id || $shift->status !== 'assigned') {
            throw new \Exception('Not authorized to clock in for this shift');
        }
    }

    private function authorizeClockOut(User $user, Shift $shift): void
    {
        $this->authorizeClockIn($user, $shift);
    }

    private function getShiftRelations(User $user): array
    {
        return match ($user->role) {
            'employee' => ['employer', 'location'],
            'agency_admin', 'agent' => ['employer', 'employee', 'location'],
            'employer_admin', 'contact' => ['agency', 'employee', 'location'],
            default => []
        };
    }

    private function shouldIncludeEntity(string $entityType, string $targetEntity): bool
    {
        return $entityType === 'all' || $entityType === $targetEntity;
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
}
