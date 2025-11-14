<?php

namespace App\Services;

use App\Models\User;
use App\Models\Shift;
use App\Models\Assignment;
use App\Models\AgencyEmployee;
use App\Models\TimeOffRequest;
use App\Models\EmployeeAvailability;
use App\Models\ShiftOffer;
use App\Models\Timesheet;
use App\Models\ShiftRequest;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CalendarEventsService
{
    public function __construct(private CalendarFiltersService $filtersService) {}

    public function getEventsForUser(User $user, array $filters): Collection
    {
        $events = collect();
        if ($this->shouldIncludeEntity($filters, 'shifts')) {
            $query = $this->buildShiftsQuery($user);
            $filteredShifts = $this->filtersService->applyFilters($query, $filters, $user->role)->get();
            $events = $events->merge($this->formatShiftsAsEvents($filteredShifts));
        }
        if ($this->shouldIncludeEntity($filters, 'shift_offers')) {
            $query = $this->buildShiftOffersQuery($user);
            $filteredOffers = $this->filtersService->applyFilters($query, $filters, $user->role)->get();
            $events = $events->merge($this->formatShiftOffersAsEvents($filteredOffers));
        }
        if ($this->shouldIncludeEntity($filters, 'availability')) {
            $query = $this->buildAvailabilityQuery($user);
            $filteredAvailability = $this->filtersService->applyFilters($query, $filters, $user->role)->get();
            $events = $events->merge($this->formatAvailabilityAsEvents($filteredAvailability));
        }
        if ($this->shouldIncludeEntity($filters, 'time_off')) {
            $query = $this->buildTimeOffQuery($user);
            $filteredTimeOff = $this->filtersService->applyFilters($query, $filters, $user->role)->get();
            $events = $events->merge($this->formatTimeOffAsEvents($filteredTimeOff));
        }
        if ($this->shouldIncludeEntity($filters, 'shift_requests')) {
            $query = $this->buildShiftRequestsQuery($user);
            $filteredRequests = $this->filtersService->applyFilters($query, $filters, $user->role)->get();
            $events = $events->merge($this->formatShiftRequestsAsEvents($filteredRequests));
        }
        return $events->sortBy('start_time');
    }

    public function getUpcomingShifts(User $user): Collection
    {
        $query = Shift::with(['assignment.agencyEmployee.employee.user', 'location.employer']);
        $filters = [
            'date_range' => ['start' => now()->toDateString(), 'end' => now()->addWeek()->toDateString()],
            'status' => ['scheduled', 'in_progress']
        ];
        return match ($user->role) {
            'employee' => $this->filtersService->applyFilters(
                $query->whereHas('assignment.agencyEmployee.employee.user', fn($q) => $q->where('id', $user->id)),
                $filters,
                $user->role
            )->orderBy('start_time')->limit(20)->get(),
            'agency_admin', 'agent' => $this->filtersService->applyFilters(
                $query->whereHas('assignment.agencyEmployee.agency', fn($q) => $q->where('id', $user->agency->id)),
                $filters,
                $user->role
            )->orderBy('start_time')->limit(50)->get(),
            'employer_admin', 'contact' => $this->filtersService->applyFilters(
                $query->whereHas('location.employer', fn($q) => $q->where('id', $user->employer->id)),
                $filters,
                $user->role
            )->orderBy('start_time')->limit(50)->get(),
            default => collect()
        };
    }

    public function getPendingActions(User $user): array
    {
        $actions = [];
        if (in_array($user->role, ['agency_admin', 'agent'])) {
            $agencyId = $user->agency->id;
            $actions['shift_offers_pending'] = ShiftOffer::whereHas(
                'shift.assignment.agencyEmployee',
                fn($q) => $q->where('agency_id', $agencyId)
            )->where('status', 'pending')->count();
            $actions['timesheets_pending_agency'] = Timesheet::whereHas(
                'shift.assignment.agencyEmployee',
                fn($q) => $q->where('agency_id', $agencyId)
            )->where('status', 'pending')->count();
            $actions['time_off_requests_pending'] = TimeOffRequest::where('agency_id', $agencyId)->where('status', 'pending')->count();
        }
        if (in_array($user->role, ['employer_admin', 'contact'])) {
            $employerId = $user->employer->id;
            $actions['timesheets_pending_employer'] = Timesheet::whereHas(
                'shift.location.employer',
                fn($q) => $q->where('id', $employerId)
            )->where('status', 'agency_approved')->count();
            $actions['shift_approvals_pending'] = DB::table('shift_approvals')
                ->join('shifts', 'shift_approvals.shift_id', '=', 'shifts.id')
                ->join('locations', 'shifts.location_id', '=', 'locations.id')
                ->where('locations.employer_id', $employerId)
                ->where('shift_approvals.status', 'pending')->count();
        }
        if ($user->role === 'employee') {
            $employeeId = $user->employee->id;
            $actions['shift_offers_pending'] = ShiftOffer::whereHas(
                'agencyEmployee.employee',
                fn($q) => $q->where('id', $employeeId)
            )->where('status', 'pending')->count();
            $actions['shifts_today'] = Shift::whereHas(
                'assignment.agencyEmployee.employee',
                fn($q) => $q->where('id', $employeeId)
            )->whereDate('start_time', today())
                ->whereIn('status', ['scheduled', 'in_progress'])->count();
        }
        return $actions;
    }

    public function getAvailabilityConflicts(User $user): Collection
    {
        if ($user->role !== 'employee') return collect();
        $employeeId = $user->employee->id;
        return Shift::from('shifts as s1')
            ->select([
                's1.id as shift_id',
                's1.start_time as shift_start',
                's1.end_time as shift_end',
                's2.id as conflicting_shift_id',
                's2.start_time as conflicting_start',
                's2.end_time as conflicting_end'
            ])
            ->join('assignments as a1', 's1.assignment_id', '=', 'a1.id')
            ->join('agency_employees as ae1', 'a1.agency_employee_id', '=', 'ae1.id')
            ->join('shifts as s2', 's1.id', '!=', 's2.id')
            ->join('assignments as a2', 's2.assignment_id', '=', 'a2.id')
            ->join('agency_employees as ae2', 'a2.agency_employee_id', '=', 'ae2.id')
            ->where('ae1.employee_id', $employeeId)
            ->where('ae2.employee_id', $employeeId)
            ->whereRaw('s1.start_time < s2.end_time')
            ->whereRaw('s1.end_time > s2.start_time')
            ->whereIn('s1.status', ['scheduled', 'in_progress'])
            ->whereIn('s2.status', ['scheduled', 'in_progress'])
            ->get();
    }

    public function getEventStats(User $user): array
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();
        return match ($user->role) {
            'employee' => $this->getEmployeeStats($user, $startDate, $endDate),
            'agency_admin', 'agent' => $this->getAgencyStats($user, $startDate, $endDate),
            'employer_admin', 'contact' => $this->getEmployerStats($user, $startDate, $endDate),
            default => []
        };
    }

    public function getWorkloadOverview(User $user): array
    {
        $overview = [];
        $startDate = now()->startOfWeek();
        $endDate = now()->addWeeks(2)->endOfWeek();
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dayShifts = $this->getShiftsForDate($user, $date);
            $overview[$date->toDateString()] = [
                'total_shifts' => $dayShifts->count(),
                'scheduled_hours' => $dayShifts->sum(fn($shift) => $shift->end_time->diffInHours($shift->start_time)),
                'filled_shifts' => $dayShifts->where('status', '!=', 'cancelled')->count(),
                'urgent_shifts' => $dayShifts->where('status', 'scheduled')->filter(fn($shift) => $shift->start_time->diffInHours(now()) < 24)->count()
            ];
        }
        return $overview;
    }

    public function executeEventAction(User $user, string $eventType, string $eventId, array $data): array
    {
        return DB::transaction(function () use ($user, $eventType, $eventId, $data) {
            return match ($eventType) {
                'shift_offer' => $this->handleShiftOfferAction($user, $eventId, $data),
                'time_off' => $this->handleTimeOffAction($user, $eventId, $data),
                'timesheet' => $this->handleTimesheetAction($user, $eventId, $data),
                default => throw new \InvalidArgumentException("Unknown event type: {$eventType}")
            };
        });
    }

    public function offerShift(User $user, string $shiftId, array $data): array
    {
        $this->validateAgencyAccess($user);
        return DB::transaction(function () use ($user, $shiftId, $data) {
            $shift = Shift::findOrFail($shiftId);
            $agencyEmployee = AgencyEmployee::findOrFail($data['agency_employee_id']);
            $this->validateShiftOffer($shift, $agencyEmployee, $user);
            $shiftOffer = ShiftOffer::create([
                'shift_id' => $shift->id,
                'agency_employee_id' => $agencyEmployee->id,
                'offered_by_id' => $user->id,
                'status' => 'pending',
                'expires_at' => Carbon::parse($data['expires_at']),
                'responded_at' => null,
                'response_notes' => null
            ]);
            return ['shift_offer' => $shiftOffer->load(['agencyEmployee.employee.user', 'shift']), 'message' => 'Shift offered successfully'];
        });
    }

    public function respondToShiftOffer(User $user, string $offerId, array $data): array
    {
        $this->validateEmployeeAccess($user);
        return DB::transaction(function () use ($user, $offerId, $data) {
            $shiftOffer = ShiftOffer::whereHas('agencyEmployee.employee.user', fn($q) => $q->where('id', $user->id))->findOrFail($offerId);
            if ($shiftOffer->status !== 'pending') throw new \Exception('Shift offer already responded to');
            if ($shiftOffer->expires_at && $shiftOffer->expires_at->isPast()) throw new \Exception('Shift offer has expired');
            $shiftOffer->update(['status' => $data['status'], 'responded_at' => now(), 'response_notes' => $data['notes'] ?? null]);
            if ($data['status'] === 'accepted') $shiftOffer->shift->update(['status' => 'scheduled']);
            return ['shift_offer' => $shiftOffer->load(['shift', 'agencyEmployee.employee.user']), 'message' => 'Shift offer response recorded'];
        });
    }

    public function clockIn(User $user, string $shiftId, array $data): array
    {
        $this->validateEmployeeAccess($user);
        return DB::transaction(function () use ($user, $shiftId, $data) {
            $shift = Shift::whereHas('assignment.agencyEmployee.employee.user', fn($q) => $q->where('id', $user->id))->findOrFail($shiftId);
            if ($shift->status !== 'scheduled') throw new \Exception('Shift cannot be clocked into');
            if ($shift->start_time->isFuture()) throw new \Exception('Cannot clock in before shift start time');
            $timesheet = Timesheet::updateOrCreate(['shift_id' => $shift->id], ['clock_in' => now(), 'status' => 'in_progress', 'notes' => $data['notes'] ?? null]);
            $shift->update(['status' => 'in_progress']);
            return ['timesheet' => $timesheet, 'shift' => $shift, 'message' => 'Clocked in successfully'];
        });
    }

    public function clockOut(User $user, string $shiftId, array $data): array
    {
        $this->validateEmployeeAccess($user);
        return DB::transaction(function () use ($user, $shiftId, $data) {
            $shift = Shift::whereHas('assignment.agencyEmployee.employee.user', fn($q) => $q->where('id', $user->id))->findOrFail($shiftId);
            $timesheet = Timesheet::where('shift_id', $shift->id)->firstOrFail();
            if ($timesheet->status !== 'in_progress') throw new \Exception('Shift is not in progress');
            $timesheet->update(['clock_out' => now(), 'break_minutes' => $data['break_minutes'], 'status' => 'pending', 'notes' => $data['notes'] ?? null]);
            $hoursWorked = $this->calculateHoursWorked($timesheet);
            $timesheet->update(['hours_worked' => $hoursWorked]);
            $shift->update(['status' => 'completed']);
            return ['timesheet' => $timesheet->fresh(), 'shift' => $shift, 'message' => 'Clocked out successfully'];
        });
    }

    public function approveTimesheet(User $user, string $timesheetId, array $data): array
    {
        return DB::transaction(function () use ($user, $timesheetId, $data) {
            $timesheet = Timesheet::findOrFail($timesheetId);
            $approvalType = $data['approval_type'];
            $this->validateTimesheetApproval($user, $timesheet, $approvalType);
            if ($approvalType === 'agency') {
                $timesheet->update(['agency_approved_by_id' => $user->id, 'agency_approved_at' => now(), 'status' => 'agency_approved']);
            } else {
                $timesheet->update(['employer_approved_by_id' => $user->id, 'employer_approved_at' => now(), 'status' => 'employer_approved']);
            }
            return ['timesheet' => $timesheet->fresh(), 'message' => 'Timesheet approved successfully'];
        });
    }

    public function updateAvailability(User $user, array $data): array
    {
        $this->validateEmployeeAccess($user);
        return DB::transaction(function () use ($user, $data) {
            $employeeId = $user->employee->id;
            EmployeeAvailability::where('employee_id', $employeeId)->delete();
            $availabilities = collect($data['availabilities'])->map(function ($availability) use ($employeeId) {
                return EmployeeAvailability::create(array_merge($availability, ['employee_id' => $employeeId]));
            });
            return ['availabilities' => $availabilities, 'message' => 'Availability updated successfully'];
        });
    }

    public function requestTimeOff(User $user, array $data): array
    {
        $this->validateEmployeeAccess($user);
        return DB::transaction(function () use ($user, $data) {
            $employeeId = $user->employee->id;
            $this->validateTimeOffRequest($employeeId, $data);
            $timeOffRequest = TimeOffRequest::create(array_merge($data, ['employee_id' => $employeeId, 'status' => 'pending']));
            return ['time_off_request' => $timeOffRequest->load(['employee.user', 'agency']), 'message' => 'Time off request submitted successfully'];
        });
    }

    public function approveTimeOff(User $user, string $timeOffId, array $data): array
    {
        $this->validateAgencyAccess($user);
        return DB::transaction(function () use ($user, $timeOffId, $data) {
            $timeOffRequest = TimeOffRequest::where('agency_id', $user->agency->id)->findOrFail($timeOffId);
            $timeOffRequest->update(['status' => $data['status'], 'approved_by_id' => $user->id, 'approved_at' => now()]);
            return ['time_off_request' => $timeOffRequest->fresh(), 'message' => 'Time off request updated successfully'];
        });
    }

    public function checkShiftConflicts(array $data): array
    {
        $conflicts = DB::select("
            SELECT s.id, s.start_time, s.end_time, a.name as agency_name, l.name as location_name
            FROM shifts s
            JOIN assignments a ON s.assignment_id = a.id
            JOIN agency_employees ae ON a.agency_employee_id = ae.id
            JOIN agencies ag ON ae.agency_id = ag.id
            JOIN locations l ON s.location_id = l.id
            WHERE ae.employee_id = ?
            AND s.status IN ('scheduled', 'in_progress')
            AND s.start_time < ? AND s.end_time > ?
        ", [$data['employee_id'], $data['end_time'], $data['start_time']]);
        return $conflicts;
    }

    public function getCalendarConfig(User $user): array
    {
        $config = [
            'event_types' => [
                'shift' => ['color' => '#3B82F6', 'icon' => 'clock'],
                'shift_offer' => ['color' => '#F59E0B', 'icon' => 'offer'],
                'availability' => ['color' => '#8B5CF6', 'icon' => 'calendar'],
                'time_off' => ['color' => '#10B981', 'icon' => 'beach'],
                'shift_request' => ['color' => '#EF4444', 'icon' => 'request']
            ],
            'permissions' => $this->getUserPermissions($user)
        ];
        return array_merge($config, $this->getRoleSpecificConfig($user));
    }

    private function buildShiftsQuery(User $user): \Illuminate\Database\Eloquent\Builder
    {
        $query = Shift::with(['assignment.agencyEmployee.employee.user', 'assignment.agencyEmployee.agency', 'location.employer']);
        return match ($user->role) {
            'employee' => $query->whereHas('assignment.agencyEmployee.employee.user', fn($q) => $q->where('id', $user->id)),
            'agency_admin', 'agent' => $query->whereHas('assignment.agencyEmployee.agency', fn($q) => $q->where('id', $user->agency->id)),
            'employer_admin', 'contact' => $query->whereHas('location.employer', fn($q) => $q->where('id', $user->employer->id)),
            default => $query->whereNull('id')
        };
    }

    private function buildShiftOffersQuery(User $user): \Illuminate\Database\Eloquent\Builder
    {
        $query = ShiftOffer::with(['shift', 'agencyEmployee.agency']);
        return $user->role === 'employee' ? $query->whereHas('agencyEmployee.employee.user', fn($q) => $q->where('id', $user->id)) : $query->whereNull('id');
    }

    private function buildAvailabilityQuery(User $user): \Illuminate\Database\Eloquent\Builder
    {
        $query = EmployeeAvailability::query();
        return $user->role === 'employee' ? $query->where('employee_id', $user->employee->id) : $query->whereNull('id');
    }

    private function buildTimeOffQuery(User $user): \Illuminate\Database\Eloquent\Builder
    {
        $query = TimeOffRequest::with(['employee.user', 'agency']);
        if ($user->role === 'employee') return $query->where('employee_id', $user->employee->id);
        if (in_array($user->role, ['agency_admin', 'agent'])) return $query->where('agency_id', $user->agency->id);
        return $query->whereNull('id');
    }

    private function buildShiftRequestsQuery(User $user): \Illuminate\Database\Eloquent\Builder
    {
        $query = ShiftRequest::with(['employer', 'location']);
        if (in_array($user->role, ['employer_admin'])) return $query->where('employer_id', $user->employer->id);
        if (in_array($user->role, ['agency_admin', 'agent'])) return $query->where(function ($q) use ($user) {
            $q->where('target_agencies', 'all')->orWhereJsonContains('specific_agency_ids', $user->agency->id);
        });
        return $query->whereNull('id');
    }

    private function formatShiftsAsEvents(Collection $shifts): Collection
    {
        return $shifts->map(function ($shift) {
            return [
                'id' => 'shift_' . $shift->id,
                'type' => 'shift',
                'title' => $shift->assignment->agencyEmployee->employee->user->name . ' - ' . $shift->location->name,
                'start_time' => $shift->start_time,
                'end_time' => $shift->end_time,
                'status' => $shift->status,
                'entity' => $shift,
                'color' => $this->getShiftColor($shift->status),
                'requires_action' => $this->shiftRequiresAction($shift)
            ];
        });
    }

    private function formatShiftOffersAsEvents(Collection $offers): Collection
    {
        return $offers->map(function ($offer) {
            return [
                'id' => 'shift_offer_' . $offer->id,
                'type' => 'shift_offer',
                'title' => 'Shift Offer - ' . $offer->shift->location->name,
                'start_time' => $offer->shift->start_time,
                'end_time' => $offer->shift->end_time,
                'status' => $offer->status,
                'entity' => $offer,
                'color' => '#F59E0B',
                'requires_action' => $offer->status === 'pending'
            ];
        });
    }

    private function formatAvailabilityAsEvents(Collection $availability): Collection
    {
        return $availability->map(function ($avail) {
            return [
                'id' => 'availability_' . $avail->id,
                'type' => 'availability',
                'title' => 'Available: ' . $avail->type,
                'start_time' => Carbon::parse($avail->start_date)->startOfDay(),
                'end_time' => $avail->end_date ? Carbon::parse($avail->end_date)->endOfDay() : Carbon::parse($avail->start_date)->endOfDay(),
                'status' => $avail->type,
                'entity' => $avail,
                'color' => '#8B5CF6',
                'requires_action' => false
            ];
        });
    }

    private function formatTimeOffAsEvents(Collection $timeOff): Collection
    {
        return $timeOff->map(function ($request) {
            return [
                'id' => 'time_off_' . $request->id,
                'type' => 'time_off',
                'title' => 'Time Off: ' . $request->type,
                'start_time' => Carbon::parse($request->start_date)->startOfDay(),
                'end_time' => Carbon::parse($request->end_date)->endOfDay(),
                'status' => $request->status,
                'entity' => $request,
                'color' => $request->status === 'approved' ? '#10B981' : '#F59E0B',
                'requires_action' => $request->status === 'pending'
            ];
        });
    }

    private function formatShiftRequestsAsEvents(Collection $requests): Collection
    {
        return $requests->map(function ($request) {
            return [
                'id' => 'shift_request_' . $request->id,
                'type' => 'shift_request',
                'title' => 'Shift Request: ' . $request->title,
                'start_time' => $request->response_deadline ?? Carbon::parse($request->start_date)->startOfDay(),
                'end_time' => $request->response_deadline ?? Carbon::parse($request->start_date)->endOfDay(),
                'status' => $request->status,
                'entity' => $request,
                'color' => '#EF4444',
                'requires_action' => $request->status === 'published' && $request->response_deadline > now()
            ];
        });
    }

    private function getEmployeeStats(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $employeeId = $user->employee->id;
        $shifts = Shift::whereHas('assignment.agencyEmployee.employee', fn($q) => $q->where('id', $employeeId))
            ->whereBetween('start_time', [$startDate, $endDate])->get();
        return [
            'total_shifts' => $shifts->count(),
            'completed_shifts' => $shifts->where('status', 'completed')->count(),
            'scheduled_shifts' => $shifts->where('status', 'scheduled')->count(),
            'total_hours' => $shifts->where('status', 'completed')->sum(fn($shift) => $shift->end_time->diffInHours($shift->start_time)),
            'pending_offers' => ShiftOffer::whereHas('agencyEmployee.employee', fn($q) => $q->where('id', $employeeId))->where('status', 'pending')->count()
        ];
    }

    private function getAgencyStats(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $agencyId = $user->agency->id;
        $shifts = Shift::whereHas('assignment.agencyEmployee.agency', fn($q) => $q->where('id', $agencyId))
            ->whereBetween('start_time', [$startDate, $endDate])->get();
        return [
            'total_shifts' => $shifts->count(),
            'filled_shifts' => $shifts->where('status', '!=', 'cancelled')->count(),
            'completed_shifts' => $shifts->where('status', 'completed')->count(),
            'pending_approvals' => Timesheet::whereHas('shift.assignment.agencyEmployee.agency', fn($q) => $q->where('id', $agencyId))->where('status', 'pending')->count(),
            'active_employees' => AgencyEmployee::where('agency_id', $agencyId)->where('status', 'active')->count()
        ];
    }

    private function getEmployerStats(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $employerId = $user->employer->id;
        $shifts = Shift::whereHas('location.employer', fn($q) => $q->where('id', $employerId))
            ->whereBetween('start_time', [$startDate, $endDate])->get();
        return [
            'total_shifts' => $shifts->count(),
            'filled_shifts' => $shifts->where('status', '!=', 'cancelled')->count(),
            'pending_approvals' => Timesheet::whereHas('shift.location.employer', fn($q) => $q->where('id', $employerId))->where('status', 'agency_approved')->count(),
            'active_assignments' => Assignment::whereHas('contract.employer', fn($q) => $q->where('id', $employerId))->where('status', 'active')->count()
        ];
    }

    private function getShiftsForDate(User $user, Carbon $date): Collection
    {
        $query = Shift::with(['assignment.agencyEmployee.employee.user', 'location']);
        return match ($user->role) {
            'employee' => $query->whereHas('assignment.agencyEmployee.employee.user', fn($q) => $q->where('id', $user->id))->whereDate('start_time', $date)->get(),
            'agency_admin', 'agent' => $query->whereHas('assignment.agencyEmployee.agency', fn($q) => $q->where('id', $user->agency->id))->whereDate('start_time', $date)->get(),
            'employer_admin', 'contact' => $query->whereHas('location.employer', fn($q) => $q->where('id', $user->employer->id))->whereDate('start_time', $date)->get(),
            default => collect()
        };
    }

    private function handleShiftOfferAction(User $user, string $offerId, array $data): array
    {
        $shiftOffer = ShiftOffer::findOrFail($offerId);
        $this->validateShiftOfferAccess($user, $shiftOffer);
        $shiftOffer->update(['status' => $data['status'], 'responded_at' => now(), 'response_notes' => $data['notes'] ?? null]);
        return ['shift_offer' => $shiftOffer->fresh()];
    }

    private function handleTimeOffAction(User $user, string $timeOffId, array $data): array
    {
        $timeOffRequest = TimeOffRequest::findOrFail($timeOffId);
        $this->validateTimeOffAccess($user, $timeOffRequest);
        $timeOffRequest->update(['status' => $data['status'], 'approved_by_id' => $user->id, 'approved_at' => now()]);
        return ['time_off_request' => $timeOffRequest->fresh()];
    }

    private function handleTimesheetAction(User $user, string $timesheetId, array $data): array
    {
        $timesheet = Timesheet::findOrFail($timesheetId);
        $this->validateTimesheetAccess($user, $timesheet);
        $timesheet->update(['status' => $data['status']]);
        return ['timesheet' => $timesheet->fresh()];
    }

    private function validateShiftOffer(Shift $shift, AgencyEmployee $agencyEmployee, User $user): void
    {
        if ($agencyEmployee->agency_id !== $user->agency->id) throw new \Exception('Agency employee does not belong to your agency');
        if ($shift->assignment->agencyEmployee->agency_id !== $user->agency->id) throw new \Exception('Shift does not belong to your agency');
        $existingOffer = ShiftOffer::where('shift_id', $shift->id)->where('agency_employee_id', $agencyEmployee->id)->where('status', 'pending')->exists();
        if ($existingOffer) throw new \Exception('Shift already offered to this employee');
        $conflicts = $this->checkShiftConflicts(['employee_id' => $agencyEmployee->employee_id, 'start_time' => $shift->start_time, 'end_time' => $shift->end_time]);
        if (count($conflicts) > 0) throw new \Exception('Employee has conflicting shifts');
    }

    private function validateTimeOffRequest(int $employeeId, array $data): void
    {
        $conflictingShifts = Shift::whereHas('assignment.agencyEmployee.employee', fn($q) => $q->where('id', $employeeId))
            ->where(function ($q) use ($data) {
                $q->whereBetween('start_time', [$data['start_date'], $data['end_date']])
                    ->orWhereBetween('end_time', [$data['start_date'], $data['end_date']]);
            })->whereIn('status', ['scheduled', 'in_progress'])->exists();
        if ($conflictingShifts) throw new \Exception('Time off request conflicts with scheduled shifts');
    }

    private function validateTimesheetApproval(User $user, Timesheet $timesheet, string $approvalType): void
    {
        if ($approvalType === 'agency') {
            if (!in_array($user->role, ['agency_admin', 'agent'])) throw new \Exception('Not authorized for agency approval');
            if ($timesheet->shift->assignment->agencyEmployee->agency_id !== $user->agency->id) throw new \Exception('Timesheet does not belong to your agency');
        } else {
            if (!in_array($user->role, ['employer_admin', 'contact'])) throw new \Exception('Not authorized for employer approval');
            if ($timesheet->shift->location->employer_id !== $user->employer->id) throw new \Exception('Timesheet does not belong to your employer');
        }
    }

    private function calculateHoursWorked(Timesheet $timesheet): float
    {
        $totalMinutes = $timesheet->clock_out->diffInMinutes($timesheet->clock_in);
        $breakMinutes = $timesheet->break_minutes;
        return ($totalMinutes - $breakMinutes) / 60;
    }

    private function validateAgencyAccess(User $user): void
    {
        if (!in_array($user->role, ['agency_admin', 'agent']) || !$user->agency) throw new \Exception('Agency access required');
    }

    private function validateEmployeeAccess(User $user): void
    {
        if ($user->role !== 'employee' || !$user->employee) throw new \Exception('Employee access required');
    }

    private function validateShiftOfferAccess(User $user, ShiftOffer $shiftOffer): void
    {
        if ($user->role === 'employee') {
            if ($shiftOffer->agencyEmployee->employee->user_id !== $user->id) throw new \Exception('Not authorized to access this shift offer');
        } elseif (in_array($user->role, ['agency_admin', 'agent'])) {
            if ($shiftOffer->agencyEmployee->agency_id !== $user->agency->id) throw new \Exception('Shift offer does not belong to your agency');
        } else throw new \Exception('Not authorized');
    }

    private function validateTimeOffAccess(User $user, TimeOffRequest $timeOffRequest): void
    {
        if (in_array($user->role, ['agency_admin', 'agent'])) {
            if ($timeOffRequest->agency_id !== $user->agency->id) throw new \Exception('Time off request does not belong to your agency');
        } elseif ($user->role === 'employee') {
            if ($timeOffRequest->employee->user_id !== $user->id) throw new \Exception('Not authorized to access this time off request');
        } else throw new \Exception('Not authorized');
    }

    private function validateTimesheetAccess(User $user, Timesheet $timesheet): void
    {
        if ($user->role === 'employee') {
            if ($timesheet->shift->assignment->agencyEmployee->employee->user_id !== $user->id) throw new \Exception('Not authorized to access this timesheet');
        } elseif (in_array($user->role, ['agency_admin', 'agent'])) {
            if ($timesheet->shift->assignment->agencyEmployee->agency_id !== $user->agency->id) throw new \Exception('Timesheet does not belong to your agency');
        } elseif (in_array($user->role, ['employer_admin', 'contact'])) {
            if ($timesheet->shift->location->employer_id !== $user->employer->id) throw new \Exception('Timesheet does not belong to your employer');
        } else throw new \Exception('Not authorized');
    }

    private function getUserPermissions(User $user): array
    {
        return match ($user->role) {
            'employee' => ['view_own_shifts', 'respond_to_offers', 'clock_in_out', 'manage_availability', 'request_time_off'],
            'agency_admin' => ['view_agency_shifts', 'offer_shifts', 'approve_timesheets', 'manage_agency_employees', 'approve_time_off'],
            'agent' => ['view_agency_shifts', 'offer_shifts', 'view_timesheets'],
            'employer_admin' => ['view_employer_shifts', 'approve_timesheets', 'create_shift_requests', 'view_reports'],
            'contact' => ['view_employer_shifts', 'approve_timesheets'],
            default => []
        };
    }

    private function getRoleSpecificConfig(User $user): array
    {
        return match ($user->role) {
            'employee' => ['default_view' => 'day', 'show_agency_context' => true],
            'agency_admin', 'agent' => ['default_view' => 'week', 'show_employee_details' => true],
            'employer_admin', 'contact' => ['default_view' => 'month', 'show_agency_context' => true],
            default => ['default_view' => 'month']
        };
    }

    private function getShiftColor(string $status): string
    {
        return match ($status) {
            'scheduled' => '#3B82F6',
            'in_progress' => '#8B5CF6',
            'completed' => '#10B981',
            'cancelled' => '#6B7280',
            default => '#9CA3AF'
        };
    }

    private function shiftRequiresAction(Shift $shift): bool
    {
        if ($shift->status === 'in_progress' && !$shift->timesheet) return true;
        if ($shift->status === 'completed' && $shift->timesheet?->status === 'pending') return true;
        return false;
    }

    private function shouldIncludeEntity(array $filters, string $entity): bool
    {
        return !isset($filters['entity_types']) || in_array($entity, $filters['entity_types']);
    }
}
