<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\CreateAgentRequest;
use App\Http\Requests\Agency\CreatePlacementRequest;
use App\Http\Requests\Agency\ProcessPayrollRequest;
use App\Http\Requests\Agency\UpdateEmployeeRequest;
use App\Http\Requests\Agency\UpdatePlacementRequest;
use App\Http\Resources\DashboardStatsResource;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\PlacementResource;
use App\Http\Resources\ShiftResource;
use App\Http\Resources\TimesheetResource;
use App\Http\Resources\UserResource;
use App\Models\Employee;
use App\Models\Placement;
use App\Models\ShiftTemplate;
use App\Models\Timesheet;
use App\Services\AgencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgencyController extends Controller
{
    public function __construct(
        private AgencyService $agencyService
    ) {
    }

    public function dashboardStats(): JsonResponse
    {
        $agency = auth()->user()->agency;
        $stats = $this->agencyService->getDashboardStats($agency);

        return response()->json([
            'success' => true,
            'data' => new DashboardStatsResource($stats),
            'message' => 'Dashboard stats retrieved successfully'
        ]);
    }

    public function approveTimesheet(Timesheet $timesheet): JsonResponse
    {
        $this->authorize('approve', $timesheet);
        $timesheet = $this->agencyService->approveTimesheet($timesheet);

        return response()->json([
            'success' => true,
            'data' => new TimesheetResource($timesheet),
            'message' => 'Timesheet approved successfully'
        ]);
    }

    public function createAgent(CreateAgentRequest $request): JsonResponse
    {
        $agency = auth()->user()->agency;
        $agent = $this->agencyService->createAgent($agency, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new UserResource($agent),
            'message' => 'Agent created successfully'
        ]);
    }

    public function createPlacement(CreatePlacementRequest $request): JsonResponse
    {
        $agency = auth()->user()->agency;
        $placement = $this->agencyService->createPlacement($agency, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new PlacementResource($placement),
            'message' => 'Placement created successfully'
        ]);
    }

    public function createShiftFromTemplate(ShiftTemplate $template, Request $request): JsonResponse
    {
        $this->authorize('use', $template);
        $request->validate(['date' => 'required|date']);
        $shift = $this->agencyService->createShiftFromTemplate($template, $request->date);

        return response()->json([
            'success' => true,
            'data' => new ShiftResource($shift),
            'message' => 'Shift created from template successfully'
        ]);
    }

    public function getAvailableEmployees(): JsonResponse
    {
        $agency = auth()->user()->agency;
        $employees = $this->agencyService->getAvailableEmployees($agency);

        return response()->json([
            'success' => true,
            'data' => EmployeeResource::collection($employees),
            'message' => 'Available employees retrieved successfully'
        ]);
    }

    public function getContacts(Request $request): JsonResponse
    {
        $agency = auth()->user()->agency;
        $contacts = $this->agencyService->getContacts($agency, $request->all());

        return response()->json([
            'success' => true,
            'data' => $contacts,
            'message' => 'Contacts retrieved successfully'
        ]);
    }

    public function getEmployees(Request $request): JsonResponse
    {
        $agency = auth()->user()->agency;
        $employees = $this->agencyService->getEmployees($agency, $request->all());

        return response()->json([
            'success' => true,
            'data' => $employees,
            'message' => 'Employees retrieved successfully'
        ]);
    }

    public function getEmployerLinks(): JsonResponse
    {
        $agency = auth()->user()->agency;
        $links = $this->agencyService->getEmployerLinks($agency);

        return response()->json([
            'success' => true,
            'data' => $links,
            'message' => 'Employer links retrieved successfully'
        ]);
    }

    public function getInvoices(Request $request): JsonResponse
    {
        $agency = auth()->user()->agency;
        $invoices = $this->agencyService->getInvoices($agency, $request->all());

        return response()->json([
            'success' => true,
            'data' => $invoices,
            'message' => 'Invoices retrieved successfully'
        ]);
    }

    public function getPayroll(Request $request): JsonResponse
    {
        $agency = auth()->user()->agency;
        $payroll = $this->agencyService->getPayroll($agency, $request->all());

        return response()->json([
            'success' => true,
            'data' => $payroll,
            'message' => 'Payroll retrieved successfully'
        ]);
    }

    public function getPayouts(): JsonResponse
    {
        $agency = auth()->user()->agency;
        $payouts = $this->agencyService->getPayouts($agency);

        return response()->json([
            'success' => true,
            'data' => $payouts,
            'message' => 'Payouts retrieved successfully'
        ]);
    }

    public function getPlacements(Request $request): JsonResponse
    {
        $agency = auth()->user()->agency;
        $placements = $this->agencyService->getPlacements($agency, $request->all());

        return response()->json([
            'success' => true,
            'data' => $placements,
            'message' => 'Placements retrieved successfully'
        ]);
    }

    public function getShifts(Request $request): JsonResponse
    {
        $agency = auth()->user()->agency;
        $shifts = $this->agencyService->getShifts($agency, $request->all());

        return response()->json([
            'success' => true,
            'data' => $shifts,
            'message' => 'Shifts retrieved successfully'
        ]);
    }

    public function getSubscriptions(): JsonResponse
    {
        $agency = auth()->user()->agency;
        $subscriptions = $this->agencyService->getSubscriptions($agency);

        return response()->json([
            'success' => true,
            'data' => $subscriptions,
            'message' => 'Subscriptions retrieved successfully'
        ]);
    }

    public function getTimesheets(Request $request): JsonResponse
    {
        $agency = auth()->user()->agency;
        $timesheets = $this->agencyService->getTimesheets($agency, $request->all());

        return response()->json([
            'success' => true,
            'data' => $timesheets,
            'message' => 'Timesheets retrieved successfully'
        ]);
    }

    public function processPayroll(ProcessPayrollRequest $request): JsonResponse
    {
        $agency = auth()->user()->agency;
        $payroll = $this->agencyService->processPayroll($agency, $request->validated());

        return response()->json([
            'success' => true,
            'data' => $payroll,
            'message' => 'Payroll processed successfully'
        ]);
    }

    public function offerEmployeeForShift($shiftId, Request $request): JsonResponse
    {
        $request->validate(['employee_id' => 'required|exists:employees,id']);
        $agency = auth()->user()->agency;
        $shiftOffer = $this->agencyService->offerEmployeeForShift($agency, $shiftId, $request->employee_id);

        return response()->json([
            'success' => true,
            'data' => $shiftOffer,
            'message' => 'Employee offered for shift successfully'
        ]);
    }

    public function updateEmployee(Employee $employee, UpdateEmployeeRequest $request): JsonResponse
    {
        $this->authorize('update', $employee);
        $employee = $this->agencyService->updateEmployee($employee, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new EmployeeResource($employee),
            'message' => 'Employee updated successfully'
        ]);
    }

    public function updatePlacement(Placement $placement, UpdatePlacementRequest $request): JsonResponse
    {
        $this->authorize('update', $placement);
        $placement = $this->agencyService->updatePlacement($placement, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new PlacementResource($placement),
            'message' => 'Placement updated successfully'
        ]);
    }
}
