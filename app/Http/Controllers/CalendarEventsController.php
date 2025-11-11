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
        $events = $this->calendarService->getEventsForUser(
            $request->user(),
            $request->validated()
        );

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

    public function upcomingShifts(Request $request): JsonResponse
    {
        $shifts = $this->calendarService->getUpcomingShifts($request->user());

        return response()->json([
            'success' => true,
            'data' => CalendarEventResource::collection($shifts)
        ]);
    }

    public function pendingActions(Request $request): JsonResponse
    {
        $events = $this->calendarService->getPendingActions($request->user());

        return response()->json([
            'success' => true,
            'data' => CalendarEventResource::collection($events)
        ]);
    }

    public function urgentShifts(Request $request): JsonResponse
    {
        $shifts = $this->calendarService->getUrgentShifts($request->user());

        return response()->json([
            'success' => true,
            'data' => CalendarEventResource::collection($shifts)
        ]);
    }

    public function eventStats(Request $request): JsonResponse
    {
        $stats = $this->calendarService->getEventStats($request->user());

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function workloadOverview(Request $request): JsonResponse
    {
        $overview = $this->calendarService->getWorkloadOverview($request->user());

        return response()->json([
            'success' => true,
            'data' => $overview
        ]);
    }

    public function getConfig(Request $request): JsonResponse
    {
        $roleConfig = $this->filtersService->getRoleBasedConfig($request->user()->role, $request);

        return response()->json([
            'success' => true,
            'data' => $roleConfig
        ]);
    }

    public function getFilterOptions(Request $request): JsonResponse
    {
        $options = $this->calendarService->getFilterOptions($request->user());

        return response()->json([
            'success' => true,
            'data' => $options
        ]);
    }

    public function executeAction(CalendarActionRequest $request, string $event): JsonResponse
    {
        $result = $this->calendarService->executeEventAction(
            $request->user(),
            $event,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Action completed successfully'
        ]);
    }

    public function offerShift(Request $request, string $shift): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'expires_at' => 'sometimes|date|after:now'
        ]);

        $result = $this->calendarService->offerShiftToEmployee(
            $request->user(),
            $shift,
            $validated
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Shift offered successfully'
        ]);
    }

    public function assignShift(Request $request, string $shift): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id'
        ]);

        $result = $this->calendarService->assignShiftToEmployee(
            $request->user(),
            $shift,
            $validated
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Shift assigned successfully'
        ]);
    }

    public function completeShift(Request $request, string $shift): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'sometimes|string|max:500'
        ]);

        $result = $this->calendarService->completeShift(
            $request->user(),
            $shift,
            $validated
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Shift completed successfully'
        ]);
    }

    public function approveShift(Request $request, string $shift): JsonResponse
    {
        $validated = $request->validate([
            'approval_type' => 'required|in:agency,employer',
            'notes' => 'sometimes|string|max:500'
        ]);

        $result = $this->calendarService->approveShift(
            $request->user(),
            $shift,
            $validated
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Shift approved successfully'
        ]);
    }

    public function clockIn(Request $request, string $shift): JsonResponse
    {
        $validated = $request->validate([
            'location' => 'sometimes|array',
            'notes' => 'sometimes|string|max:500'
        ]);

        $result = $this->calendarService->clockIn(
            $request->user(),
            $shift,
            $validated
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Clocked in successfully'
        ]);
    }

    public function clockOut(Request $request, string $shift): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'sometimes|string|max:500',
            'break_minutes' => 'sometimes|integer|min:0'
        ]);

        $result = $this->calendarService->clockOut(
            $request->user(),
            $shift,
            $validated
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Clocked out successfully'
        ]);
    }

    public function getAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date'
        ]);

        $availability = $this->calendarService->getEmployeeAvailability(
            $request->user(),
            $validated
        );

        return response()->json([
            'success' => true,
            'data' => $availability
        ]);
    }

    public function updateAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'availabilities' => 'required|array',
            'availabilities.*.type' => 'required|in:recurring,one_time',
            'availabilities.*.day_of_week' => 'required_if:type,recurring',
            'availabilities.*.start_date' => 'required_if:type,one_time|date',
            'availabilities.*.end_date' => 'required_if:type,one_time|date|after:start_date',
            'availabilities.*.start_time' => 'required|date_format:H:i',
            'availabilities.*.end_time' => 'required|date_format:H:i|after:start_time'
        ]);

        $result = $this->calendarService->updateEmployeeAvailability(
            $request->user(),
            $validated
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Availability updated successfully'
        ]);
    }

    public function requestTimeOff(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:vacation,sick,personal,bereavement,other',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'reason' => 'sometimes|string|max:1000',
            'attachments' => 'sometimes|array'
        ]);

        $result = $this->calendarService->requestTimeOff(
            $request->user(),
            $validated
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Time off request submitted successfully'
        ]);
    }

    public function bulkOfferShifts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shift_ids' => 'required|array',
            'shift_ids.*' => 'exists:shifts,id',
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'expires_at' => 'sometimes|date|after:now'
        ]);

        $result = $this->calendarService->bulkOfferShifts(
            $request->user(),
            $validated
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Shifts offered successfully'
        ]);
    }

    public function bulkAssignShifts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shift_ids' => 'required|array',
            'shift_ids.*' => 'exists:shifts,id',
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id'
        ]);

        $result = $this->calendarService->bulkAssignShifts(
            $request->user(),
            $validated
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Shifts assigned successfully'
        ]);
    }

    public function exportEvents(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'format' => 'sometimes|in:csv,excel,pdf',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'filters' => 'sometimes|array'
        ]);

        $export = $this->calendarService->exportEvents(
            $request->user(),
            $validated
        );

        return response()->json([
            'success' => true,
            'data' => $export,
            'message' => 'Events exported successfully'
        ]);
    }

    public function printSchedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'view' => 'sometimes|in:week,month',
            'include_unassigned' => 'sometimes|boolean'
        ]);

        $schedule = $this->calendarService->generatePrintableSchedule(
            $request->user(),
            $validated
        );

        return response()->json([
            'success' => true,
            'data' => $schedule,
            'message' => 'Schedule generated successfully'
        ]);
    }
}
