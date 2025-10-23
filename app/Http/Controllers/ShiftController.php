<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateShiftRequest;
use App\Http\Requests\UpdateShiftRequest;
use App\Http\Resources\ShiftResource;
use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function __construct(
        private ShiftService $shiftService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $shifts = $this->shiftService->getShifts($request->all());
        return response()->json([
            'success' => true,
            'data' => $shifts,
            'message' => 'Shifts retrieved successfully'
        ]);
    }

    public function store(CreateShiftRequest $request): JsonResponse
    {
        $shift = $this->shiftService->createShift($request->validated());
        return response()->json([
            'success' => true,
            'data' => new ShiftResource($shift),
            'message' => 'Shift created successfully'
        ]);
    }

    public function show(Shift $shift): JsonResponse
    {
        $shift->load(['employer', 'agency', 'placement', 'employee.user', 'agent', 'location', 'timesheet', 'shiftApprovals']);
        return response()->json([
            'success' => true,
            'data' => new ShiftResource($shift),
            'message' => 'Shift retrieved successfully'
        ]);
    }

    public function update(UpdateShiftRequest $request, Shift $shift): JsonResponse
    {
        $shift = $this->shiftService->updateShift($shift, $request->validated());
        return response()->json([
            'success' => true,
            'data' => new ShiftResource($shift),
            'message' => 'Shift updated successfully'
        ]);
    }

    public function destroy(Shift $shift): JsonResponse
    {
        $this->shiftService->deleteShift($shift);
        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Shift deleted successfully'
        ]);
    }

    public function cancel(Shift $shift): JsonResponse
    {
        $this->authorize('update', $shift);
        $shift = $this->shiftService->cancelShift($shift);

        return response()->json([
            'success' => true,
            'data' => new ShiftResource($shift),
            'message' => 'Shift cancelled successfully'
        ]);
    }

    public function assignEmployee(Shift $shift, Request $request): JsonResponse
    {
        $this->authorize('update', $shift);
        $request->validate([
            'employee_id' => 'required|exists:employees,id'
        ]);

        $shift = $this->shiftService->assignEmployee($shift, $request->employee_id);

        return response()->json([
            'success' => true,
            'data' => new ShiftResource($shift),
            'message' => 'Employee assigned to shift successfully'
        ]);
    }

    public function complete(Shift $shift): JsonResponse
    {
        $this->authorize('update', $shift);
        $shift = $this->shiftService->completeShift($shift);

        return response()->json([
            'success' => true,
            'data' => new ShiftResource($shift),
            'message' => 'Shift marked as completed successfully'
        ]);
    }
}
