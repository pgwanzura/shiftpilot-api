<?php

namespace App\Http\Controllers;

use App\Models\AgencyAssignmentResponse;
use App\Http\Requests\AgencyAssignmentResponse\CreateAgencyAssignmentResponseRequest;
use App\Http\Requests\AgencyAssignmentResponse\UpdateAgencyAssignmentResponseRequest;
use App\Http\Resources\AgencyAssignmentResponseResource;
use App\Http\Resources\AgencyAssignmentResponseCollection;
use App\Services\AgencyAssignmentResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgencyAssignmentResponseController extends Controller
{
    public function __construct(private AgencyAssignmentResponseService $responseService)
    {
        $this->authorizeResource(AgencyAssignmentResponse::class, 'response');
    }

    public function index(Request $request): AgencyAssignmentResponseCollection
    {
        $filters = $request->only(['assignment_id', 'agency_id', 'status', 'search', 'sort_by', 'sort_direction']);
        $responses = $this->responseService->getPaginatedResponses($filters);

        return new AgencyAssignmentResponseCollection($responses);
    }

    public function store(CreateAgencyAssignmentResponseRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Set agency_id from authenticated user if not provided
        if (!isset($data['agency_id']) && auth()->user()->agency_id) {
            $data['agency_id'] = auth()->user()->agency_id;
        }

        $response = $this->responseService->createResponse($data);

        return response()->json([
            'message' => 'Assignment response submitted successfully.',
            'data' => new AgencyAssignmentResponseResource($response),
        ], 201);
    }

    public function show(AgencyAssignmentResponse $response): JsonResponse
    {
        $response->load(['assignment', 'agency', 'assignment.location', 'assignment.employer']);

        return response()->json([
            'data' => new AgencyAssignmentResponseResource($response),
        ]);
    }

    public function update(UpdateAgencyAssignmentResponseRequest $request, AgencyAssignmentResponse $response): JsonResponse
    {
        $response = $this->responseService->updateResponse(
            $response,
            $request->validated()
        );

        return response()->json([
            'message' => 'Assignment response updated successfully.',
            'data' => new AgencyAssignmentResponseResource($response),
        ]);
    }

    public function destroy(AgencyAssignmentResponse $response): JsonResponse
    {
        $this->responseService->deleteResponse($response);

        return response()->json([
            'message' => 'Assignment response deleted successfully.',
        ]);
    }

    // Custom actions
    public function accept(AgencyAssignmentResponse $response): JsonResponse
    {
        $this->authorize('accept', $response);

        $response = $this->responseService->acceptResponse($response);

        return response()->json([
            'message' => 'Assignment response accepted successfully.',
            'data' => new AgencyAssignmentResponseResource($response),
        ]);
    }

    public function reject(Request $request, AgencyAssignmentResponse $response): JsonResponse
    {
        $this->authorize('reject', $response);

        $request->validate([
            'rejection_reason' => 'sometimes|string|max:500',
        ]);

        $response = $this->responseService->rejectResponse(
            $response,
            $request->input('rejection_reason')
        );

        return response()->json([
            'message' => 'Assignment response rejected successfully.',
            'data' => new AgencyAssignmentResponseResource($response),
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $agencyId = $request->input('agency_id');
        $stats = $this->responseService->getResponseStats($agencyId);

        return response()->json([
            'data' => $stats,
        ]);
    }

    public function forAssignment(Request $request, int $assignmentId): JsonResponse
    {
        $responses = $this->responseService->getAssignmentResponses($assignmentId);

        return response()->json([
            'data' => AgencyAssignmentResponseResource::collection($responses),
        ]);
    }

    public function forAgency(Request $request): JsonResponse
    {
        $agencyId = auth()->user()->agency_id;

        if (!$agencyId) {
            return response()->json([
                'message' => 'User is not associated with an agency.',
            ], 403);
        }

        $filters = $request->only(['status', 'assignment_id', 'per_page']);
        $responses = $this->responseService->getAgencyResponses($agencyId, $filters);

        return response()->json([
            'data' => new AgencyAssignmentResponseCollection($responses),
        ]);
    }
}
