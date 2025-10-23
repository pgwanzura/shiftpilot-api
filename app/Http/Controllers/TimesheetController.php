<?php

namespace App\Http\Controllers;

use App\Http\Requests\Timesheet\CreateTimesheetRequest;
use App\Http\Requests\Timesheet\UpdateTimesheetRequest;
use App\Http\Resources\TimesheetResource;
use App\Models\Timesheet;
use App\Services\TimesheetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimesheetController extends Controller
{
    public function __construct(
        private TimesheetService $timesheetService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $timesheets = $this->timesheetService->getTimesheets($request->all());
        return response()->json([
            'success' => true,
            'data' => $timesheets,
            'message' => 'Timesheets retrieved successfully'
        ]);
    }

    public function store(CreateTimesheetRequest $request): JsonResponse
    {
        $timesheet = $this->timesheetService->createTimesheet($request->validated());
        return response()->json([
            'success' => true,
            'data' => new TimesheetResource($timesheet),
            'message' => 'Timesheet created successfully'
        ]);
    }

    public function show(Timesheet $timesheet): JsonResponse
    {
        $timesheet->load(['shift', 'employee.user', 'agencyApprovedBy', 'approvedByContact']);
        return response()->json([
            'success' => true,
            'data' => new TimesheetResource($timesheet),
            'message' => 'Timesheet retrieved successfully'
        ]);
    }

    public function update(UpdateTimesheetRequest $request, Timesheet $timesheet): JsonResponse
    {
        $timesheet = $this->timesheetService->updateTimesheet($timesheet, $request->validated());
        return response()->json([
            'success' => true,
            'data' => new TimesheetResource($timesheet),
            'message' => 'Timesheet updated successfully'
        ]);
    }

    public function destroy(Timesheet $timesheet): JsonResponse
    {
        $this->timesheetService->deleteTimesheet($timesheet);
        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Timesheet deleted successfully'
        ]);
    }

    public function clockIn(Timesheet $timesheet): JsonResponse
    {
        $this->authorize('clockIn', $timesheet);
        $timesheet = $this->timesheetService->clockIn($timesheet);

        return response()->json([
            'success' => true,
            'data' => new TimesheetResource($timesheet),
            'message' => 'Clocked in successfully'
        ]);
    }

    public function clockOut(Timesheet $timesheet): JsonResponse
    {
        $this->authorize('clockOut', $timesheet);
        $timesheet = $this->timesheetService->clockOut($timesheet);

        return response()->json([
            'success' => true,
            'data' => new TimesheetResource($timesheet),
            'message' => 'Clocked out successfully'
        ]);
    }

    public function approveAgency(Timesheet $timesheet): JsonResponse
    {
        $this->authorize('approveAgency', $timesheet);
        $timesheet = $this->timesheetService->approveAgency($timesheet);

        return response()->json([
            'success' => true,
            'data' => new TimesheetResource($timesheet),
            'message' => 'Timesheet approved by agency successfully'
        ]);
    }

    public function approveEmployer(Timesheet $timesheet): JsonResponse
    {
        $this->authorize('approveEmployer', $timesheet);
        $timesheet = $this->timesheetService->approveEmployer($timesheet);

        return response()->json([
            'success' => true,
            'data' => new TimesheetResource($timesheet),
            'message' => 'Timesheet approved by employer successfully'
        ]);
    }

    public function reject(Timesheet $timesheet): JsonResponse
    {
        $this->authorize('reject', $timesheet);
        $timesheet = $this->timesheetService->rejectTimesheet($timesheet);

        return response()->json([
            'success' => true,
            'data' => new TimesheetResource($timesheet),
            'message' => 'Timesheet rejected successfully'
        ]);
    }

    public function submit(Timesheet $timesheet): JsonResponse
    {
        $this->authorize('submit', $timesheet);
        $timesheet = $this->timesheetService->submitTimesheet($timesheet);

        return response()->json([
            'success' => true,
            'data' => new TimesheetResource($timesheet),
            'message' => 'Timesheet submitted successfully'
        ]);
    }
}
