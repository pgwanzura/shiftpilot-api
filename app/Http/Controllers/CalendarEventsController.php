<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CalendarEventsService;
use App\Services\CalendarFiltersService;
use App\Http\Requests\Calendar\CalendarEventsRequest;
use App\Http\Requests\Calendar\CalendarActionRequest;
use App\Http\Resources\CalendarEventResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarEventsController extends Controller
{
    public function __construct(
        private CalendarEventsService $calendarService,
        private CalendarFiltersService $filtersService
    ) {}

    public function index(CalendarEventsRequest $request): JsonResponse
    {
        $events = $this->calendarService->getEventsForUser($request->user(), $request->validated());
        $roleConfig = $this->filtersService->getRoleBasedConfig($request->user()->role, $request);

        return response()->json([
            'success' => true,
            'data' => CalendarEventResource::collection($events),
            'meta' => [
                'total' => $events->count(),
                'filters' => $request->validated(),
                'roleConfig' => $roleConfig
            ]
        ]);
    }

    public function getFilterOptions(Request $request): JsonResponse
    {
        $options = $this->filtersService->getAvailableFilters($request->user()->role);
        return response()->json(['success' => true, 'data' => $options]);
    }

    public function getCalendarConfig(Request $request): JsonResponse
    {
        $config = $this->calendarService->getCalendarConfig($request->user());
        $filterConfig = $this->filtersService->getRoleBasedConfig($request->user()->role, $request);
        return response()->json(['success' => true, 'data' => array_merge($config, $filterConfig)]);
    }

    public function upcomingShifts(Request $request): JsonResponse
    {
        $shifts = $this->calendarService->getUpcomingShifts($request->user());
        return response()->json(['success' => true, 'data' => CalendarEventResource::collection($shifts)]);
    }

    public function pendingActions(Request $request): JsonResponse
    {
        $actions = $this->calendarService->getPendingActions($request->user());
        return response()->json(['success' => true, 'data' => $actions]);
    }

    public function availabilityConflicts(Request $request): JsonResponse
    {
        $conflicts = $this->calendarService->getAvailabilityConflicts($request->user());
        return response()->json(['success' => true, 'data' => $conflicts]);
    }

    public function eventStats(Request $request): JsonResponse
    {
        $stats = $this->calendarService->getEventStats($request->user());
        return response()->json(['success' => true, 'data' => $stats]);
    }

    public function workloadOverview(Request $request): JsonResponse
    {
        $overview = $this->calendarService->getWorkloadOverview($request->user());
        return response()->json(['success' => true, 'data' => $overview]);
    }

    public function executeAction(CalendarActionRequest $request, string $eventType, string $eventId): JsonResponse
    {
        $result = $this->calendarService->executeEventAction($request->user(), $eventType, $eventId, $request->validated());
        return response()->json(['success' => true, 'data' => $result, 'message' => 'Action completed successfully']);
    }

    public function offerShift(Request $request, string $shiftId): JsonResponse
    {
        $validated = $request->validate([
            'agency_employee_id' => 'required|exists:agency_employees,id',
            'expires_at' => 'required|date|after:now'
        ]);
        $result = $this->calendarService->offerShift($request->user(), $shiftId, $validated);
        return response()->json(['success' => true, 'data' => $result, 'message' => 'Shift offered successfully']);
    }

    public function respondToShiftOffer(Request $request, string $offerId): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:accepted,rejected',
            'notes' => 'sometimes|string|max:500'
        ]);
        $result = $this->calendarService->respondToShiftOffer($request->user(), $offerId, $validated);
        return response()->json(['success' => true, 'data' => $result, 'message' => 'Shift offer response submitted']);
    }

    public function clockIn(Request $request, string $shiftId): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'sometimes|string|max:500',
            'location_verified' => 'sometimes|boolean'
        ]);
        $result = $this->calendarService->clockIn($request->user(), $shiftId, $validated);
        return response()->json(['success' => true, 'data' => $result, 'message' => 'Clocked in successfully']);
    }

    public function clockOut(Request $request, string $shiftId): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'sometimes|string|max:500',
            'break_minutes' => 'required|integer|min:0|max:180'
        ]);
        $result = $this->calendarService->clockOut($request->user(), $shiftId, $validated);
        return response()->json(['success' => true, 'data' => $result, 'message' => 'Clocked out successfully']);
    }

    public function approveTimesheet(Request $request, string $timesheetId): JsonResponse
    {
        $validated = $request->validate([
            'approval_type' => 'required|in:agency,employer',
            'notes' => 'sometimes|string|max:500'
        ]);
        $result = $this->calendarService->approveTimesheet($request->user(), $timesheetId, $validated);
        return response()->json(['success' => true, 'data' => $result, 'message' => 'Timesheet approved successfully']);
    }

    public function updateAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'availabilities' => 'required|array',
            'availabilities.*.start_date' => 'required|date',
            'availabilities.*.end_date' => 'nullable|date|after_or_equal:availabilities.*.start_date',
            'availabilities.*.days_mask' => 'required|integer|min:1|max:127',
            'availabilities.*.start_time' => 'required|date_format:H:i',
            'availabilities.*.end_time' => 'required|date_format:H:i|after:availabilities.*.start_time',
            'availabilities.*.type' => 'required|in:preferred,available,unavailable'
        ]);
        $result = $this->calendarService->updateAvailability($request->user(), $validated);
        return response()->json(['success' => true, 'data' => $result, 'message' => 'Availability updated successfully']);
    }

    public function requestTimeOff(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agency_id' => 'required|exists:agencies,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:vacation,sick,personal,bereavement,other',
            'reason' => 'sometimes|string|max:1000'
        ]);
        $result = $this->calendarService->requestTimeOff($request->user(), $validated);
        return response()->json(['success' => true, 'data' => $result, 'message' => 'Time off request submitted successfully']);
    }

    public function approveTimeOff(Request $request, string $timeOffId): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes' => 'sometimes|string|max:500'
        ]);
        $result = $this->calendarService->approveTimeOff($request->user(), $timeOffId, $validated);
        return response()->json(['success' => true, 'data' => $result, 'message' => 'Time off request updated successfully']);
    }

    public function checkShiftConflicts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time'
        ]);
        $conflicts = $this->calendarService->checkShiftConflicts($validated);
        return response()->json(['success' => true, 'data' => $conflicts, 'has_conflicts' => count($conflicts) > 0]);
    }
}
