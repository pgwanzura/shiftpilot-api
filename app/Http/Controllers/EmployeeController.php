<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\SetAvailabilityRequest;
use App\Http\Requests\Employee\SubmitTimeOffRequest;
use App\Http\Requests\Employee\UpdateAvailabilityRequest;
use App\Http\Resources\DashboardStatsResource;
use App\Http\Resources\EmployeeAvailabilityResource;
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

class EmployeeController extends Controller
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
        $this->authorize('clockIn', $shift);
        $timesheet = $this->employeeService->clockIn($shift);

        return response()->json([
            'success' => true,
            'data' => new TimesheetResource($timesheet),
            'message' => 'Clocked in successfully'
        ]);
    }

    public function clockOut(Shift $shift): JsonResponse
    {
        $this->authorize('clockOut', $shift);
        $timesheet = $this->employeeService->clockOut($shift);

        return response()->json([
            'success' => true,
            'data' => new TimesheetResource($timesheet),
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

    public function getPayroll(Request $request): JsonResponse
    {
        $employee = auth()->user()->employee;
        $payroll = $this->employeeService->getPayroll($employee, $request->all());

        return response()->json([
            'success' => true,
            'data' => $payroll,
            'message' => 'Payroll retrieved successfully'
        ]);
    }

    public function getShifts(Request $request): JsonResponse
    {
        $employee = auth()->user()->employee;
        $shifts = $this->employeeService->getShifts($employee, $request->all());

        return response()->json([
            'success' => true,
            'data' => $shifts,
            'message' => 'Shifts retrieved successfully'
        ]);
    }

    public function getTimesheets(): JsonResponse
    {
        $employee = auth()->user()->employee;
        $timesheets = $this->employeeService->getTimesheets($employee);

        return response()->json([
            'success' => true,
            'data' => $timesheets,
            'message' => 'Timesheets retrieved successfully'
        ]);
    }

    public function getShiftOffers(): JsonResponse
    {
        $employee = auth()->user()->employee;
        $shiftOffers = $this->employeeService->getShiftOffers($employee);

        return response()->json([
            'success' => true,
            'data' => $shiftOffers,
            'message' => 'Shift offers retrieved successfully'
        ]);
    }

    public function respondToShiftOffer(ShiftOffer $shiftOffer, Request $request): JsonResponse
    {
        $this->authorize('respond', $shiftOffer);
        $request->validate([
            'accept' => 'required|boolean',
            'notes' => 'nullable|string'
        ]);
        $updatedOffer = $this->employeeService->respondToShiftOffer($shiftOffer, $request->accept, $request->notes);

        return response()->json([
            'success' => true,
            'data' => new ShiftOfferResource($updatedOffer),
            'message' => 'Shift offer response submitted successfully'
        ]);
    }

    public function setAvailability(SetAvailabilityRequest $request): JsonResponse
    {
        $employee = auth()->user()->employee;
        $availability = $this->employeeService->setAvailability($employee, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new EmployeeAvailabilityResource($availability),
            'message' => 'Availability set successfully'
        ]);
    }

    public function submitTimeOffRequest(SubmitTimeOffRequest $request): JsonResponse
    {
        $employee = auth()->user()->employee;
        $timeOffRequest = $this->employeeService->submitTimeOffRequest($employee, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new TimeOffRequestResource($timeOffRequest),
            'message' => 'Time off request submitted successfully'
        ]);
    }

    public function updateAvailability(EmployeeAvailability $availability, UpdateAvailabilityRequest $request): JsonResponse
    {
        $this->authorize('update', $availability);
        $updatedAvailability = $this->employeeService->updateAvailability($availability, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new EmployeeAvailabilityResource($updatedAvailability),
            'message' => 'Availability updated successfully'
        ]);
    }
}
