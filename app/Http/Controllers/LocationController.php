<?php

namespace App\Http\Controllers;

use App\Http\Requests\Location\CreateLocationRequest;
use App\Http\Requests\Location\UpdateLocationRequest;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function __construct(
        private LocationService $locationService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $locations = $this->locationService->getLocations($request->all());
        return response()->json([
            'success' => true,
            'data' => $locations,
            'message' => 'Locations retrieved successfully'
        ]);
    }

    public function store(CreateLocationRequest $request): JsonResponse
    {
        $employer = auth()->user()->employer;
        $location = $this->locationService->createLocation($employer, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new LocationResource($location),
            'message' => 'Location created successfully'
        ]);
    }

    public function show(Location $location): JsonResponse
    {
        $location->load(['employer', 'shifts']);
        return response()->json([
            'success' => true,
            'data' => new LocationResource($location),
            'message' => 'Location retrieved successfully'
        ]);
    }

    public function update(UpdateLocationRequest $request, Location $location): JsonResponse
    {
        $location = $this->locationService->updateLocation($location, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new LocationResource($location),
            'message' => 'Location updated successfully'
        ]);
    }

    public function destroy(Location $location): JsonResponse
    {
        $this->locationService->deleteLocation($location);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Location deleted successfully'
        ]);
    }
}
