<?php

namespace App\Http\Controllers;

use App\Models\AgencyPlacementResponse;
use App\Http\Requests\AgencyPlacementResponse\StoreAgencyPlacementResponseRequest;
use App\Http\Requests\AgencyPlacementResponse\UpdateAgencyPlacementResponseRequest;
use App\Services\AgencyPlacementResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgencyPlacementResponseController extends Controller
{
    protected $agencyPlacementResponseService;

    public function __construct(AgencyPlacementResponseService $agencyPlacementResponseService)
    {
        $this->agencyPlacementResponseService = $agencyPlacementResponseService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $agencyPlacementResponses = $this->agencyPlacementResponseService->getAllAgencyPlacementResponses();
        return response()->json($agencyPlacementResponses);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAgencyPlacementResponseRequest $request): JsonResponse
    {
        $agencyPlacementResponse = $this->agencyPlacementResponseService->createAgencyPlacementResponse($request->validated());
        return response()->json($agencyPlacementResponse, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(AgencyPlacementResponse $agencyPlacementResponse): JsonResponse
    {
        return response()->json($agencyPlacementResponse);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AgencyPlacementResponse $agencyPlacementResponse)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAgencyPlacementResponseRequest $request, AgencyPlacementResponse $agencyPlacementResponse): JsonResponse
    {
        $agencyPlacementResponse = $this->agencyPlacementResponseService->updateAgencyPlacementResponse($agencyPlacementResponse, $request->validated());
        return response()->json($agencyPlacementResponse);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AgencyPlacementResponse $agencyPlacementResponse): JsonResponse
    {
        $this->agencyPlacementResponseService->deleteAgencyPlacementResponse($agencyPlacementResponse);
        return response()->json(null, 204);
    }
}
