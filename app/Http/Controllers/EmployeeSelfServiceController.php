<?php

namespace App\Http\Controllers;

use App\Events\EmployeeAvailabilityUpdated;
use App\Events\ShiftOfferReceived;
use App\Events\TimeOff\TimeOffRequested;
use App\Events\Timesheet\TimesheetSubmitted;
use App\Http\Requests\Employee\SetAvailabilityRequest;
use App\Http\Requests\Employee\SubmitTimeOffRequest;
use App\Http\Requests\Employee\UpdateAvailabilityRequest;
use App\Http\Requests\EmployeePreference\UpdateEmployeePreferencesRequest;
use App\Http\Requests\Employee\RespondToShiftOfferRequest;
use App\Http\Resources\AssignmentResource;
use App\Http\Resources\DashboardStatsResource;
use App\Http\Resources\EmployeeAvailabilityResource;
use App\Http\Resources\EmployeePreferencesResource;
use App\Http\Resources\PayrollResource;
use App\Http\Resources\ShiftOfferResource;
use App\Http\Resources\ShiftResource;
use App\Http\Resources\TimeOffRequestResource;
use App\Http\Resources\TimesheetResource;
use App\Models\EmployeeAvailability;
use App\Models\Shift;
use App\Models\ShiftOffer;
use App\Models\TimeOffRequest;
use App\Models\Timesheet;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EmployeeSelfServiceController extends Controller
{
    public function __construct(
        private EmployeeService $employeeService
    ) {}

    public function dashboardStats(): JsonResponse
    {
        $employee = auth()->user()->employee;
        $stats = $this->employeeService->getDashboardStats($employee);
        return response()->json([
            'success' => true,
            'data' => new DashboardStatsResource($stats),
            'message' => 'Dashboard stats retrieved successfully'
        ]);
    }

    public function clockIn(Shift $shift): JsonResponse
    {
        Gate::authorize('clock-in', $shift);
        $employee = auth()->user()->employee;
        $timesheet = $this->employeeService->clockIn($shift, $employee);
        event(new TimesheetSubmitted($employee, $timesheet));
        return response()->json([
            'success' => true,
            'data' => new TimesheetResource($timesheet->load('shift.assignment')),
            'message' => 'Clocked in successfully'
        ]);
    }

    public function clockOut(Shift $shift): JsonResponse
    {
        Gate::authorize('clock-out', $shift);
        $employee = auth()->user()->employee;
        $timesheet = $this->employeeService->clockOut($shift, $employee);
        return response()->json([
            'success' => true,
            'data' => new TimesheetResource($timesheet->load('shift.assignment')),
            'message' => 'Clocked out successfully'
        ]);
    }

    public function getAvailability(): JsonResponse
    {
        $employee = auth()->user()->employee;
        $availability = $this->employeeService->getAvailability($employee);
        return response()->json([
            'success' => true,
            'data' => EmployeeAvailabilityResource::collection($availability),
            'message' => 'Availability retrieved successfully'
        ]);
    }

    public function getPayrollHistory(Request $request): JsonResponse
    {
        $employee = auth()->user()->employee;
        $payroll = $this->employeeService->getPayrollHistory($employee, $request->all());
        return response()->json([
            'success' => true,
            'data' => PayrollResource::collection($payroll),
            'message' => 'Payroll history retrieved successfully'
        ]);
    }

    public function getShifts(Request $request): JsonResponse
    {
        $employee = auth()->user()->employee;
        $shifts = $this->employeeService->getShifts($employee, $request->all());
        return response()->json([
            'success' => true,
            'data' => ShiftResource::collection($shifts),
            'message' => 'Shifts retrieved successfully'
        ]);
    }

    public function getTimesheets(Request $request): JsonResponse
    {
        $employee = auth()->user()->employee;
        $timesheets = $this->employeeService->getTimesheets($employee, $request->all());
        return response()->json([
            'success' => true,
            'data' => TimesheetResource::collection($timesheets),
            'message' => 'Timesheets retrieved successfully'
        ]);
    }

    public function getShiftOffers(Request $request): JsonResponse
    {
        $employee = auth()->user()->employee;
        $shiftOffers = $this->employeeService->getShiftOffers($employee, $request->all());
        return response()->json([
            'success' => true,
            'data' => ShiftOfferResource::collection($shiftOffers),
            'message' => 'Shift offers retrieved successfully'
        ]);
    }

    public function getCurrentAssignments(): JsonResponse
    {
        $employee = auth()->user()->employee;
        $assignments = $this->employeeService->getCurrentAssignments($employee);
        return response()->json([
            'success' => true,
            'data' => AssignmentResource::collection($assignments),
            'message' => 'Current assignments retrieved successfully'
        ]);
    }

    public function getPreferences(): JsonResponse
    {
        $employee = auth()->user()->employee;
        $preferences = $employee->preferences;
        return response()->json([
            'success' => true,
            'data' => $preferences ? new EmployeePreferencesResource($preferences) : null,
            'message' => 'Preferences retrieved successfully'
        ]);
    }

    public function updatePreferences(UpdateEmployeePreferencesRequest $request): JsonResponse
    {
        $employee = auth()->user()->employee;
        $preferences = $this->employeeService->updatePreferences($employee, $request->validated());
        return response()->json([
            'success' => true,
            'data' => new EmployeePreferencesResource($preferences),
            'message' => 'Preferences updated successfully'
        ]);
    }

    public function respondToShiftOffer(ShiftOffer $shiftOffer, RespondToShiftOfferRequest $request): JsonResponse
    {
        Gate::authorize('respond', $shiftOffer);
        $employee = auth()->user()->employee;
        $updatedOffer = $this->employeeService->respondToShiftOffer(
            $shiftOffer,
            $employee,
            $request->validated('accept'),
            $request->validated('notes')
        );
        return response()->json([
            'success' => true,
            'data' => new ShiftOfferResource($updatedOffer->load('shift.assignment')),
            'message' => 'Shift offer response submitted successfully'
        ]);
    }

    public function setAvailability(SetAvailabilityRequest $request): JsonResponse
    {
        $employee = auth()->user()->employee;
        $availability = $this->employeeService->setAvailability($employee, $request->validated());
        event(new EmployeeAvailabilityUpdated($employee));
        return response()->json([
            'success' => true,
            'data' => new EmployeeAvailabilityResource($availability),
            'message' => 'Availability set successfully'
        ]);
    }

    public function updateAvailability(EmployeeAvailability $availability, UpdateAvailabilityRequest $request): JsonResponse
    {
        Gate::authorize('update', $availability);
        $employee = auth()->user()->employee;
        $updatedAvailability = $this->employeeService->updateAvailability($availability, $employee, $request->validated());
        event(new EmployeeAvailabilityUpdated($employee));
        return response()->json([
            'success' => true,
            'data' => new EmployeeAvailabilityResource($updatedAvailability),
            'message' => 'Availability updated successfully'
        ]);
    }

    public function deleteAvailability(EmployeeAvailability $availability): JsonResponse
    {
        Gate::authorize('delete', $availability);
        $employee = auth()->user()->employee;
        $this->employeeService->deleteAvailability($availability, $employee);
        event(new EmployeeAvailabilityUpdated($employee));
        return response()->json([
            'success' => true,
            'message' => 'Availability deleted successfully'
        ]);
    }

    public function submitTimeOffRequest(SubmitTimeOffRequest $request): JsonResponse
    {
        $employee = auth()->user()->employee;
        $timeOffRequest = $this->employeeService->submitTimeOffRequest($employee, $request->validated());
        event(new TimeOffRequested($employee, $timeOffRequest));
        return response()->json([
            'success' => true,
            'data' => new TimeOffRequestResource($timeOffRequest->load('agency')),
            'message' => 'Time off request submitted successfully'
        ]);
    }

    public function getTimeOffRequests(Request $request): JsonResponse
    {
        $employee = auth()->user()->employee;
        $timeOffRequests = $employee->timeOffRequests()
            ->with('agency')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));
        return response()->json([
            'success' => true,
            'data' => TimeOffRequestResource::collection($timeOffRequests),
            'message' => 'Time off requests retrieved successfully'
        ]);
    }
}
