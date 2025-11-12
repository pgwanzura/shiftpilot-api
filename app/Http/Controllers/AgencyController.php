<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Http\Requests\StoreAgencyRequest;
use App\Http\Requests\UpdateAgencyRequest;
use App\Services\AgencyService;
use Illuminate\Http\JsonResponse;

class AgencyController extends Controller
{
    protected $agencyService;

    public function __construct(AgencyService $agencyService)
    {
        $this->agencyService = $agencyService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $agencies = $this->agencyService->getAllAgencies();
        return response()->json($agencies);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAgencyRequest $request): JsonResponse
    {
        $agency = $this->agencyService->createAgency($request->validated());
        return response()->json($agency, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Agency $agency): JsonResponse
    {
        return response()->json($agency);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAgencyRequest $request, Agency $agency): JsonResponse
    {
        $agency = $this->agencyService->updateAgency($agency, $request->validated());
        return response()->json($agency);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Agency $agency): JsonResponse
    {
        $this->agencyService->deleteAgency($agency);
        return response()->json(null, 204);
    }
}
