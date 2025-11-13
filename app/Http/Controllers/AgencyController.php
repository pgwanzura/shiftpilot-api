<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\AgencyEmployee;
use App\Models\Assignment;
use App\Models\Shift;
use App\Models\ShiftRequest;
use App\Models\Timesheet;
use App\Http\Requests\Agency\CreateAgencyRequest;
use App\Http\Requests\Agency\UpdateAgencyRequest;
use App\Http\Resources\AgencyCollection;
use App\Http\Resources\AgencyResource;
use App\Services\AgencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgencyController extends Controller
{
    public function __construct(private AgencyService $agencyService) {}

    public function index(Request $request): AgencyCollection
    {
        $this->authorize('viewAny', Agency::class);

        $agencies = $this->agencyService->getAgenciesWithFilters($request->all());
        return new AgencyCollection($agencies);
    }

    public function store(CreateAgencyRequest $request): JsonResponse
    {
        $agency = $this->agencyService->createAgency($request->validated(), $request->user());
        return response()->json([
            'data' => new AgencyResource($agency),
            'message' => 'Agency created successfully'
        ], 201);
    }

    public function show(Agency $agency): JsonResponse
    {
        $this->authorize('view', $agency);

        $agency->load(['user', 'headOffice', 'agents.user']);
        return response()->json([
            'data' => new AgencyResource($agency)
        ]);
    }

    public function update(UpdateAgencyRequest $request, Agency $agency): JsonResponse
    {
        $updatedAgency = $this->agencyService->updateAgency($agency, $request->validated());
        return response()->json([
            'data' => new AgencyResource($updatedAgency),
            'message' => 'Agency updated successfully'
        ]);
    }

    public function destroy(Agency $agency): JsonResponse
    {
        $this->authorize('delete', $agency);
        $deleted = $this->agencyService->deleteAgency($agency);
        if (!$deleted) {
            return response()->json([
                'message' => 'Cannot delete agency with active relationships'
            ], 422);
        }
        return response()->json([
            'message' => 'Agency deleted successfully'
        ]);
    }

    public function dashboard(Agency $agency): JsonResponse
    {
        $this->authorize('view', $agency);

        $stats = $this->agencyService->getDashboardStats($agency);
        return response()->json([
            'data' => $stats
        ]);
    }

    public function employees(Agency $agency, Request $request): JsonResponse
    {
        $this->authorize('view', $agency);

        $employees = $this->agencyService->getAgencyEmployees($agency, $request->all());
        return response()->json([
            'data' => $employees
        ]);
    }

    public function registerEmployee(Agency $agency, Request $request): JsonResponse
    {
        $this->authorize('update', $agency);

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'branch_id' => 'nullable|exists:branches,id',
            'position' => 'nullable|string|max:255',
            'pay_rate' => 'required|numeric|min:0',
            'employment_type' => 'required|in:temp,contract,temp_to_perm',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after:contract_start_date',
            'specializations' => 'nullable|array',
            'preferred_locations' => 'nullable|array',
            'max_weekly_hours' => 'nullable|integer|min:1|max:168',
        ]);
        $agencyEmployee = $this->agencyService->registerEmployeeWithAgency($agency, $validated);
        return response()->json([
            'data' => $agencyEmployee,
            'message' => 'Employee registered successfully'
        ], 201);
    }

    public function updateEmployee(Agency $agency, Request $request, $employeeId): JsonResponse
    {
        $this->authorize('update', $agency);

        $agencyEmployee = AgencyEmployee::where('agency_id', $agency->id)
            ->where('id', $employeeId)
            ->firstOrFail();
        $validated = $request->validate([
            'position' => 'nullable|string|max:255',
            'pay_rate' => 'sometimes|numeric|min:0',
            'employment_type' => 'sometimes|in:temp,contract,temp_to_perm',
            'status' => 'sometimes|in:active,inactive,suspended,terminated',
            'contract_end_date' => 'nullable|date|after:contract_start_date',
            'specializations' => 'nullable|array',
            'preferred_locations' => 'nullable|array',
            'max_weekly_hours' => 'nullable|integer|min:1|max:168',
        ]);
        $updatedEmployee = $this->agencyService->updateAgencyEmployee($agencyEmployee, $validated);
        return response()->json([
            'data' => $updatedEmployee,
            'message' => 'Employee updated successfully'
        ]);
    }

    public function assignments(Agency $agency, Request $request): JsonResponse
    {
        $this->authorize('view', $agency);

        $assignments = $this->agencyService->getAssignments($agency, $request->all());
        return response()->json([
            'data' => $assignments
        ]);
    }

    public function createAssignment(Agency $agency, Request $request): JsonResponse
    {
        $this->authorize('update', $agency);

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'employer_id' => 'required|exists:employers,id',
            'location_id' => 'required|exists:locations,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'agreed_rate' => 'required|numeric|min:0',
            'pay_rate' => 'required|numeric|min:0',
            'assignment_type' => 'required|in:standard,direct',
        ]);
        $assignment = $this->agencyService->createAssignment($agency, $validated);
        return response()->json([
            'data' => $assignment,
            'message' => 'Assignment created successfully'
        ], 201);
    }

    public function updateAssignment(Agency $agency, Request $request, $assignmentId): JsonResponse
    {
        $this->authorize('update', $agency);

        $assignment = Assignment::where('id', $assignmentId)
            ->whereHas('contract', function ($q) use ($agency) {
                $q->where('agency_id', $agency->id);
            })
            ->firstOrFail();
        $validated = $request->validate([
            'status' => 'sometimes|in:active,suspended,completed,cancelled',
            'end_date' => 'nullable|date|after:start_date',
            'agreed_rate' => 'sometimes|numeric|min:0',
            'pay_rate' => 'sometimes|numeric|min:0',
        ]);
        $updatedAssignment = $this->agencyService->updateAssignment($assignment, $validated);
        return response()->json([
            'data' => $updatedAssignment,
            'message' => 'Assignment updated successfully'
        ]);
    }

    public function contracts(Agency $agency): JsonResponse
    {
        $this->authorize('view', $agency);

        $contracts = $this->agencyService->getEmployerContracts($agency);
        return response()->json([
            'data' => $contracts
        ]);
    }

    public function syncEmployerContract(Agency $agency, Request $request, $employerId): JsonResponse
    {
        $this->authorize('update', $agency);

        $validated = $request->validate([
            'contract_start' => 'required|date',
            'contract_end' => 'nullable|date|after:contract_start',
            'terms' => 'nullable|string',
            'status' => 'required|in:pending,active,suspended,terminated',
        ]);
        $success = $this->agencyService->syncAgencyWithEmployer($agency, $employerId, $validated);
        return response()->json([
            'success' => $success,
            'message' => $success ? 'Contract synchronized successfully' : 'Failed to synchronize contract'
        ]);
    }

    public function availableEmployees(Agency $agency, Request $request): JsonResponse
    {
        $this->authorize('view', $agency);

        $employees = $this->agencyService->getAvailableAgencyEmployees($agency, $request->all());
        return response()->json([
            'data' => $employees
        ]);
    }

    public function approveTimesheet(Agency $agency, Request $request, $timesheetId): JsonResponse
    {
        $this->authorize('update', $agency);

        $timesheet = Timesheet::where('id', $timesheetId)
            ->whereHas('shift.assignment.contract', function ($q) use ($agency) {
                $q->where('agency_id', $agency->id);
            })
            ->firstOrFail();
        $approvedTimesheet = $this->agencyService->approveTimesheet($timesheet);
        return response()->json([
            'data' => $approvedTimesheet,
            'message' => 'Timesheet approved successfully'
        ]);
    }

    public function processPayroll(Agency $agency, Request $request): JsonResponse
    {
        $this->authorize('update', $agency);
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
        ]);
        $payout = $this->agencyService->processPayroll($agency, $validated);
        return response()->json([
            'data' => $payout,
            'message' => 'Payroll processed successfully'
        ]);
    }

    public function generateInvoice(Agency $agency, Request $request, $assignmentId): JsonResponse
    {
        $this->authorize('update', $agency);

        $assignment = Assignment::where('id', $assignmentId)
            ->whereHas('contract', function ($q) use ($agency) {
                $q->where('agency_id', $agency->id);
            })
            ->firstOrFail();
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
        ]);
        $invoice = $this->agencyService->generateInvoiceForAssignment($assignment, $validated);
        return response()->json([
            'data' => $invoice,
            'message' => 'Invoice generated successfully'
        ], 201);
    }

    public function submitShiftResponse(Agency $agency, Request $request, $shiftRequestId): JsonResponse
    {
        $this->authorize('update', $agency);

        $shiftRequest = ShiftRequest::findOrFail($shiftRequestId);
        $validated = $request->validate([
            'proposed_employee_id' => 'nullable|exists:employees,id',
            'proposed_rate' => 'required|numeric|min:0',
            'proposed_start_date' => 'required|date',
            'proposed_end_date' => 'nullable|date|after:proposed_start_date',
            'terms' => 'nullable|string',
            'estimated_total_hours' => 'nullable|integer|min:1',
        ]);
        $response = $this->agencyService->submitAgencyResponse($shiftRequest, $agency, $validated);
        return response()->json([
            'data' => $response,
            'message' => 'Shift response submitted successfully'
        ], 201);
    }

    public function offerShift(Agency $agency, Request $request, $shiftId): JsonResponse
    {
        $this->authorize('update', $agency);

        $shift = Shift::where('id', $shiftId)
            ->whereHas('assignment.contract', function ($q) use ($agency) {
                $q->where('agency_id', $agency->id);
            })
            ->firstOrFail();
        $validated = $request->validate([
            'agency_employee_id' => 'required|exists:agency_employees,id',
        ]);
        $offer = $this->agencyService->offerShiftToEmployee($shift, AgencyEmployee::find($validated['agency_employee_id']));
        return response()->json([
            'data' => $offer,
            'message' => 'Shift offered successfully'
        ], 201);
    }

    public function createAssignmentFromResponse(Agency $agency, Request $request, $responseId): JsonResponse
    {
        $this->authorize('update', $agency);

        $agencyResponse = \App\Models\AgencyResponse::where('id', $responseId)
            ->where('agency_id', $agency->id)
            ->where('status', 'accepted')
            ->firstOrFail();
        $assignment = $this->agencyService->createAssignmentFromResponse($agencyResponse);
        return response()->json([
            'data' => $assignment,
            'message' => 'Assignment created from response successfully'
        ], 201);
    }

    public function createShiftFromTemplate(Agency $agency, Request $request, $templateId): JsonResponse
    {
        $this->authorize('update', $agency);

        $template = \App\Models\ShiftTemplate::where('id', $templateId)
            ->whereHas('assignment.contract', function ($q) use ($agency) {
                $q->where('agency_id', $agency->id);
            })
            ->firstOrFail();
        $validated = $request->validate([
            'date' => 'required|date',
        ]);
        $shift = $this->agencyService->createShiftFromTemplate($template, $validated['date']);
        return response()->json([
            'data' => $shift,
            'message' => 'Shift created from template successfully'
        ], 201);
    }

    public function getTimesheets(Agency $agency, Request $request): JsonResponse
    {
        $this->authorize('view', $agency);
        $timesheets = $this->agencyService->getTimesheets($agency, $request->all());
        return response()->json([
            'data' => $timesheets
        ]);
    }

    public function getShifts(Agency $agency, Request $request): JsonResponse
    {
        $this->authorize('view', $agency);

        $shifts = $this->agencyService->getShifts($agency, $request->all());
        return response()->json([
            'data' => $shifts
        ]);
    }

    public function getInvoices(Agency $agency, Request $request): JsonResponse
    {
        $this->authorize('view', $agency);

        $invoices = $this->agencyService->getInvoices($agency, $request->all());
        return response()->json([
            'data' => $invoices
        ]);
    }

    public function getPayroll(Agency $agency, Request $request): JsonResponse
    {
        $this->authorize('view', $agency);

        $payroll = $this->agencyService->getPayroll($agency, $request->all());
        return response()->json([
            'data' => $payroll
        ]);
    }

    public function getPayouts(Agency $agency): JsonResponse
    {
        $this->authorize('view', $agency);

        $payouts = $this->agencyService->getPayouts($agency);
        return response()->json([
            'data' => $payouts
        ]);
    }

    public function getAgencyResponseStats(Agency $agency, Request $request): JsonResponse
    {
        $this->authorize('view', $agency);

        $stats = $this->agencyService->getAgencyResponseStats($agency, $request->all());
        return response()->json([
            'data' => $stats
        ]);
    }
}
