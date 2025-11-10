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
        try {
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch calendar events',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function upcomingShifts(Request $request): JsonResponse
    {
        try {
            $shifts = $this->calendarService->getUpcomingShifts($request->user());

            return response()->json([
                'success' => true,
                'data' => CalendarEventResource::collection($shifts)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch upcoming shifts'
            ], 500);
        }
    }

    public function pendingActions(Request $request): JsonResponse
    {
        try {
            $events = $this->calendarService->getPendingActions($request->user());

            return response()->json([
                'success' => true,
                'data' => CalendarEventResource::collection($events)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending actions'
            ], 500);
        }
    }

    public function urgentShifts(Request $request): JsonResponse
    {
        try {
            $shifts = $this->calendarService->getUrgentShifts($request->user());

            return response()->json([
                'success' => true,
                'data' => CalendarEventResource::collection($shifts)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch urgent shifts'
            ], 500);
        }
    }

    public function eventStats(Request $request): JsonResponse
    {
        try {
            $stats = $this->calendarService->getEventStats($request->user());

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch event statistics'
            ], 500);
        }
    }

    public function workloadOverview(Request $request): JsonResponse
    {
        try {
            $overview = $this->calendarService->getWorkloadOverview($request->user());

            return response()->json([
                'success' => true,
                'data' => $overview
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch workload overview'
            ], 500);
        }
    }

    public function getConfig(Request $request): JsonResponse
    {
        try {
            $roleConfig = $this->filtersService->getRoleBasedConfig($request->user()->role, $request);

            return response()->json([
                'success' => true,
                'data' => $roleConfig
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch calendar configuration'
            ], 500);
        }
    }

    public function getFilterOptions(Request $request): JsonResponse
    {
        try {
            $options = $this->calendarService->getFilterOptions($request->user());

            return response()->json([
                'success' => true,
                'data' => $options
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch filter options'
            ], 500);
        }
    }

    public function executeAction(CalendarActionRequest $request, string $event): JsonResponse
    {
        try {
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to execute action',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function offerShift(Request $request, string $shift): JsonResponse
    {
        try {
            $result = $this->calendarService->offerShiftToEmployee(
                $request->user(),
                $shift,
                $request->validate([
                    'employee_id' => 'required|exists:employees,id',
                    'expires_at' => 'sometimes|date|after:now'
                ])
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Shift offered successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to offer shift',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function assignShift(Request $request, string $shift): JsonResponse
    {
        try {
            $result = $this->calendarService->assignShiftToEmployee(
                $request->user(),
                $shift,
                $request->validate([
                    'employee_id' => 'required|exists:employees,id'
                ])
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Shift assigned successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign shift',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function completeShift(Request $request, string $shift): JsonResponse
    {
        try {
            $result = $this->calendarService->completeShift(
                $request->user(),
                $shift,
                $request->validate([
                    'notes' => 'sometimes|string|max:500'
                ])
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Shift completed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete shift',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function approveShift(Request $request, string $shift): JsonResponse
    {
        try {
            $result = $this->calendarService->approveShift(
                $request->user(),
                $shift,
                $request->validate([
                    'approval_type' => 'required|in:agency,employer',
                    'notes' => 'sometimes|string|max:500'
                ])
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Shift approved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve shift',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function clockIn(Request $request, string $shift): JsonResponse
    {
        try {
            $result = $this->calendarService->clockIn(
                $request->user(),
                $shift,
                $request->validate([
                    'location' => 'sometimes|array',
                    'notes' => 'sometimes|string|max:500'
                ])
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Clocked in successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clock in',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function clockOut(Request $request, string $shift): JsonResponse
    {
        try {
            $result = $this->calendarService->clockOut(
                $request->user(),
                $shift,
                $request->validate([
                    'notes' => 'sometimes|string|max:500',
                    'break_minutes' => 'sometimes|integer|min:0'
                ])
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Clocked out successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clock out',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function getAvailability(Request $request): JsonResponse
    {
        try {
            $availability = $this->calendarService->getEmployeeAvailability(
                $request->user(),
                $request->validate([
                    'start_date' => 'sometimes|date',
                    'end_date' => 'sometimes|date|after_or_equal:start_date'
                ])
            );

            return response()->json([
                'success' => true,
                'data' => $availability
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch availability'
            ], 500);
        }
    }

    public function updateAvailability(Request $request): JsonResponse
    {
        try {
            $result = $this->calendarService->updateEmployeeAvailability(
                $request->user(),
                $request->validate([
                    'availabilities' => 'required|array',
                    'availabilities.*.type' => 'required|in:recurring,one_time',
                    'availabilities.*.day_of_week' => 'required_if:type,recurring',
                    'availabilities.*.start_date' => 'required_if:type,one_time|date',
                    'availabilities.*.end_date' => 'required_if:type,one_time|date|after:start_date',
                    'availabilities.*.start_time' => 'required|date_format:H:i',
                    'availabilities.*.end_time' => 'required|date_format:H:i|after:start_time'
                ])
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Availability updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update availability',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function requestTimeOff(Request $request): JsonResponse
    {
        try {
            $result = $this->calendarService->requestTimeOff(
                $request->user(),
                $request->validate([
                    'type' => 'required|in:vacation,sick,personal,bereavement,other',
                    'start_date' => 'required|date',
                    'end_date' => 'required|date|after_or_equal:start_date',
                    'start_time' => 'sometimes|date_format:H:i',
                    'end_time' => 'sometimes|date_format:H:i|after:start_time',
                    'reason' => 'sometimes|string|max:1000',
                    'attachments' => 'sometimes|array'
                ])
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Time off request submitted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit time off request',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function bulkOfferShifts(Request $request): JsonResponse
    {
        try {
            $result = $this->calendarService->bulkOfferShifts(
                $request->user(),
                $request->validate([
                    'shift_ids' => 'required|array',
                    'shift_ids.*' => 'exists:shifts,id',
                    'employee_ids' => 'required|array',
                    'employee_ids.*' => 'exists:employees,id',
                    'expires_at' => 'sometimes|date|after:now'
                ])
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Shifts offered successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to offer shifts',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function bulkAssignShifts(Request $request): JsonResponse
    {
        try {
            $result = $this->calendarService->bulkAssignShifts(
                $request->user(),
                $request->validate([
                    'shift_ids' => 'required|array',
                    'shift_ids.*' => 'exists:shifts,id',
                    'employee_ids' => 'required|array',
                    'employee_ids.*' => 'exists:employees,id'
                ])
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Shifts assigned successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign shifts',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function exportEvents(Request $request): JsonResponse
    {
        try {
            $export = $this->calendarService->exportEvents(
                $request->user(),
                $request->validate([
                    'format' => 'sometimes|in:csv,excel,pdf',
                    'start_date' => 'sometimes|date',
                    'end_date' => 'sometimes|date|after_or_equal:start_date',
                    'filters' => 'sometimes|array'
                ])
            );

            return response()->json([
                'success' => true,
                'data' => $export,
                'message' => 'Events exported successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export events',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function printSchedule(Request $request): JsonResponse
    {
        try {
            $schedule = $this->calendarService->generatePrintableSchedule(
                $request->user(),
                $request->validate([
                    'start_date' => 'sometimes|date',
                    'end_date' => 'sometimes|date|after_or_equal:start_date',
                    'view' => 'sometimes|in:week,month',
                    'include_unassigned' => 'sometimes|boolean'
                ])
            );

            return response()->json([
                'success' => true,
                'data' => $schedule,
                'message' => 'Schedule generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate schedule',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
