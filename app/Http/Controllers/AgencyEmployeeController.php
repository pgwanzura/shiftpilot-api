<?php

namespace App\Http\Controllers;

use App\Models\AgencyEmployee;
use App\Http\Requests\AgencyEmployee\StoreAgencyEmployeeRequest;
use App\Http\Requests\AgencyEmployee\UpdateAgencyEmployeeRequest;
use App\Services\AgencyEmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgencyEmployeeController extends Controller
{
    protected $agencyEmployeeService;

    public function __construct(AgencyEmployeeService $agencyEmployeeService)
    {
        $this->agencyEmployeeService = $agencyEmployeeService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $agencyEmployees = $this->agencyEmployeeService->getAllAgencyEmployees();
        return response()->json($agencyEmployees);
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
    public function store(StoreAgencyEmployeeRequest $request): JsonResponse
    {
        $agencyEmployee = $this->agencyEmployeeService->createAgencyEmployee($request->validated());
        return response()->json($agencyEmployee, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(AgencyEmployee $agencyEmployee): JsonResponse
    {
        return response()->json($agencyEmployee);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AgencyEmployee $agencyEmployee)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAgencyEmployeeRequest $request, AgencyEmployee $agencyEmployee): JsonResponse
    {
        $agencyEmployee = $this->agencyEmployeeService->updateAgencyEmployee($agencyEmployee, $request->validated());
        return response()->json($agencyEmployee);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AgencyEmployee $agencyEmployee): JsonResponse
    {
        $this->agencyEmployeeService->deleteAgencyEmployee($agencyEmployee);
        return response()->json(null, 204);
    }
}
