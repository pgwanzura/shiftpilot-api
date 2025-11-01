<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Placement\CreatePlacementRequest;
use App\Http\Requests\Placement\UpdatePlacementRequest;
use App\Http\Requests\Placement\PlacementFilterRequest;
use App\Http\Resources\PlacementResource;
use App\Http\Resources\PlacementCollection;
use App\Models\Placement;
use App\Services\PlacementService;
use App\Enums\PlacementStatus;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PlacementController extends Controller
{
    public function __construct(private PlacementService $placementService) {}

    public function index(PlacementFilterRequest $request): PlacementCollection
    {
        $placements = $this->placementService->getFilteredPlacements($request->validated());

        return new PlacementCollection($placements);
    }

    public function store(CreatePlacementRequest $request): JsonResponse
    {
        $placement = $this->placementService->createPlacement($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Placement created successfully',
            'data' => new PlacementResource($placement),
        ], Response::HTTP_CREATED);
    }

    public function show(Placement $placement): JsonResponse
    {
        $placement->load([
            'location',
            'employer',
            'createdBy',
            'agencyResponses.agency',
            'agencyResponses.employee',
            'shifts',
            'selectedAgency',
            'selectedEmployee'
        ]);

        return response()->json([
            'status' => 'success',
            'data' => new PlacementResource($placement),
        ]);
    }

    public function update(UpdatePlacementRequest $request, Placement $placement): JsonResponse
    {
        $updatedPlacement = $this->placementService->updatePlacement(
            $placement,
            $request->validated()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Placement updated successfully',
            'data' => new PlacementResource($updatedPlacement),
        ]);
    }

    public function destroy(Placement $placement): JsonResponse
    {
        if ($placement->status !== PlacementStatus::DRAFT->value) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only draft placements can be deleted',
            ], Response::HTTP_FORBIDDEN);
        }

        $placement->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Placement deleted successfully',
        ]);
    }

    public function activate(Placement $placement): JsonResponse
    {
        try {
            $this->placementService->activatePlacement($placement);

            return response()->json([
                'status' => 'success',
                'message' => 'Placement activated successfully',
                'data' => new PlacementResource($placement->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function close(Placement $placement): JsonResponse
    {
        try {
            $this->placementService->closePlacement($placement);

            return response()->json([
                'status' => 'success',
                'message' => 'Placement closed successfully',
                'data' => new PlacementResource($placement->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function cancel(Placement $placement): JsonResponse
    {
        try {
            $reason = request('reason');
            $this->placementService->cancelPlacement($placement, $reason);

            return response()->json([
                'status' => 'success',
                'message' => 'Placement cancelled successfully',
                'data' => new PlacementResource($placement->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function stats(): JsonResponse
    {
        $stats = $this->placementService->getPlacementStats();

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }

    /**
     * Get comprehensive placement statistics
     * Replaces the mock sync function from the frontend
     */
    public function getPlacementStats(): JsonResponse
    {
        try {
            $stats = [
                'total' => Placement::count(),
                'active' => Placement::where('status', PlacementStatus::ACTIVE->value)->count(),
                'draft' => Placement::where('status', PlacementStatus::DRAFT->value)->count(),
                'filled' => Placement::where('status', PlacementStatus::FILLED->value)->count(),
                'completed' => Placement::where('status', PlacementStatus::COMPLETED->value)->count(),
                'responses' => $this->getTotalResponses(),
            ];

            return response()->json([
                'status' => 'success',
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch placement statistics',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Calculate total responses across all placements
     */
    private function getTotalResponses(): int
    {
        // Assuming you have a relationship or model for agency responses
        // Adjust this based on your actual database structure
        if (class_exists('App\Models\AgencyResponse')) {
            return \App\Models\AgencyResponse::count();
        }
        
        // Alternative: if responses are stored differently
        // You might need to adjust this based on your actual data structure
        return Placement::withCount('agencyResponses')->get()->sum('agency_responses_count');
    }
}