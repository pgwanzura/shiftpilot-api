<?php

namespace App\Http\Controllers;

use App\Http\Requests\TimeOff\CreateTimeOffRequest;
use App\Http\Requests\TimeOff\UpdateTimeOffRequest;
use App\Http\Resources\TimeOffResource;
use App\Models\TimeOffRequest;
use App\Services\TimeOffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimeOffController extends Controller
{
    public function __construct(
        private TimeOffService $timeOffService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $timeOffRequests = $this->timeOffService->getTimeOffRequests($request->all());
        return response()->json([
            'success' => true,
            'data' => $timeOffRequests,
            'message' => 'Time off requests retrieved successfully'
        ]);
    }

    public function store(CreateTimeOffRequest $request): JsonResponse
    {
        $timeOffRequest = $this->timeOffService->createTimeOffRequest($request->validated());
        return response()->json([
            'success' => true,
            'data' => new TimeOffResource($timeOffRequest),
            'message' => 'Time off request created successfully'
        ]);
    }

    public function show(TimeOffRequest $timeOffRequest): JsonResponse
    {
        $timeOffRequest->load(['employee.user', 'approvedBy']);
        return response()->json([
            'success' => true,
            'data' => new TimeOffResource($timeOffRequest),
            'message' => 'Time off request retrieved successfully'
        ]);
    }

    public function update(UpdateTimeOffRequest $request, TimeOffRequest $timeOffRequest): JsonResponse
    {
        $timeOffRequest = $this->timeOffService->updateTimeOffRequest($timeOffRequest, $request->validated());
        return response()->json([
            'success' => true,
            'data' => new TimeOffResource($timeOffRequest),
            'message' => 'Time off request updated successfully'
        ]);
    }

    public function destroy(TimeOffRequest $timeOffRequest): JsonResponse
    {
        $this->timeOffService->deleteTimeOffRequest($timeOffRequest);
        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Time off request deleted successfully'
        ]);
    }

    public function approve(TimeOffRequest $timeOffRequest): JsonResponse
    {
        $this->authorize('approve', $timeOffRequest);
        $timeOffRequest = $this->timeOffService->approveTimeOffRequest($timeOffRequest);

        return response()->json([
            'success' => true,
            'data' => new TimeOffResource($timeOffRequest),
            'message' => 'Time off request approved successfully'
        ]);
    }

    public function reject(TimeOffRequest $timeOffRequest): JsonResponse
    {
        $this->authorize('approve', $timeOffRequest);
        $timeOffRequest = $this->timeOffService->rejectTimeOffRequest($timeOffRequest);

        return response()->json([
            'success' => true,
            'data' => new TimeOffResource($timeOffRequest),
            'message' => 'Time off request rejected successfully'
        ]);
    }

    public function cancel(TimeOffRequest $timeOffRequest): JsonResponse
    {
        $this->authorize('update', $timeOffRequest);
        $timeOffRequest = $this->timeOffService->cancelTimeOffRequest($timeOffRequest);

        return response()->json([
            'success' => true,
            'data' => new TimeOffResource($timeOffRequest),
            'message' => 'Time off request cancelled successfully'
        ]);
    }
}
