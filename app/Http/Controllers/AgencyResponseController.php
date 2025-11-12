<?php

namespace App\Http\Controllers;

use App\Models\AgencyResponse;
use App\Http\Requests\AgencyResponse\StoreAgencyResponseRequest;
use App\Http\Requests\AgencyResponse\UpdateAgencyResponseRequest;
use App\Services\AgencyResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgencyResponseController extends Controller
{
    protected $agencyResponseService;

    public function __construct(AgencyResponseService $agencyResponseService)
    {
        $this->agencyResponseService = $agencyResponseService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $agencyResponses = $this->agencyResponseService->getAllAgencyResponses();
        return response()->json($agencyResponses);
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
    public function store(StoreAgencyResponseRequest $request): JsonResponse
    {
        $agencyResponse = $this->agencyResponseService->createAgencyResponse($request->validated());
        return response()->json($agencyResponse, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(AgencyResponse $agencyResponse): JsonResponse
    {
        return response()->json($agencyResponse);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AgencyResponse $agencyResponse)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAgencyResponseRequest $request, AgencyResponse $agencyResponse): JsonResponse
    {
        $agencyResponse = $this->agencyResponseService->updateAgencyResponse($agencyResponse, $request->validated());
        return response()->json($agencyResponse);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AgencyResponse $agencyResponse): JsonResponse
    {
        $this->agencyResponseService->deleteAgencyResponse($agencyResponse);
        return response()->json(null, 204);
    }
}
