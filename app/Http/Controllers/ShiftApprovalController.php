<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShiftApproval\CreateShiftApprovalRequest;
use App\Http\Requests\ShiftApproval\UpdateShiftApprovalRequest;
use App\Http\Resources\ShiftApprovalResource;
use App\Models\ShiftApproval;
use App\Services\ShiftApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftApprovalController extends Controller
{
    public function __construct(
        private ShiftApprovalService $shiftApprovalService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $approvals = $this->shiftApprovalService->getShiftApprovals($request->all());
        return response()->json([
            'success' => true,
            'data' => $approvals,
            'message' => 'Shift approvals retrieved successfully'
        ]);
    }

    public function store(CreateShiftApprovalRequest $request): JsonResponse
    {
        $approval = $this->shiftApprovalService->createShiftApproval($request->validated());
        return response()->json([
            'success' => true,
            'data' => new ShiftApprovalResource($approval),
            'message' => 'Shift approval created successfully'
        ]);
    }

    public function show(ShiftApproval $approval): JsonResponse
    {
        $approval->load(['shift', 'contact.user']);
        return response()->json([
            'success' => true,
            'data' => new ShiftApprovalResource($approval),
            'message' => 'Shift approval retrieved successfully'
        ]);
    }

    public function update(UpdateShiftApprovalRequest $request, ShiftApproval $approval): JsonResponse
    {
        $approval = $this->shiftApprovalService->updateShiftApproval($approval, $request->validated());
        return response()->json([
            'success' => true,
            'data' => new ShiftApprovalResource($approval),
            'message' => 'Shift approval updated successfully'
        ]);
    }

    public function destroy(ShiftApproval $approval): JsonResponse
    {
        $this->shiftApprovalService->deleteShiftApproval($approval);
        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Shift approval deleted successfully'
        ]);
    }

    public function approve(ShiftApproval $approval): JsonResponse
    {
        $this->authorize('update', $approval);
        $approval = $this->shiftApprovalService->approveShiftApproval($approval);

        return response()->json([
            'success' => true,
            'data' => new ShiftApprovalResource($approval),
            'message' => 'Shift approval approved successfully'
        ]);
    }

    public function reject(ShiftApproval $approval): JsonResponse
    {
        $this->authorize('update', $approval);
        $approval = $this->shiftApprovalService->rejectShiftApproval($approval);

        return response()->json([
            'success' => true,
            'data' => new ShiftApprovalResource($approval),
            'message' => 'Shift approval rejected successfully'
        ]);
    }
}
