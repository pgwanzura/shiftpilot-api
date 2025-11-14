<?php

namespace App\Http\Controllers;

use App\Events\EmployeeAvailabilityUpdated;
use App\Http\Requests\Employee\SetAvailabilityRequest;
use App\Http\Requests\Employee\UpdateAvailabilityRequest;
use App\Http\Resources\EmployeeAvailabilityResource;
use App\Models\EmployeeAvailability;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EmployeeAvailabilityController extends Controller
{
    public function __construct(
        private EmployeeService $employeeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $employee = auth()->user()->employee;
        $availability = $this->employeeService->getAvailability($employee);

        return response()->json([
            'success' => true,
            'data' => EmployeeAvailabilityResource::collection($availability),
            'message' => 'Availability retrieved successfully'
        ]);
    }

    public function store(SetAvailabilityRequest $request): JsonResponse
    {
        $employee = auth()->user()->employee;
        $availability = $this->employeeService->setAvailability($employee, $request->validated());

        event(new EmployeeAvailabilityUpdated($employee));

        return response()->json([
            'success' => true,
            'data' => new EmployeeAvailabilityResource($availability),
            'message' => 'Availability created successfully'
        ], 201);
    }

    public function show(EmployeeAvailability $employeeAvailability): JsonResponse
    {
        Gate::authorize('view', $employeeAvailability);

        return response()->json([
            'success' => true,
            'data' => new EmployeeAvailabilityResource($employeeAvailability),
            'message' => 'Availability retrieved successfully'
        ]);
    }

    public function update(UpdateAvailabilityRequest $request, EmployeeAvailability $employeeAvailability): JsonResponse
    {
        Gate::authorize('update', $employeeAvailability);

        $employee = auth()->user()->employee;
        $updatedAvailability = $this->employeeService->updateAvailability($employeeAvailability, $employee, $request->validated());

        event(new EmployeeAvailabilityUpdated($employee));

        return response()->json([
            'success' => true,
            'data' => new EmployeeAvailabilityResource($updatedAvailability),
            'message' => 'Availability updated successfully'
        ]);
    }

    public function destroy(EmployeeAvailability $employeeAvailability): JsonResponse
    {
        Gate::authorize('delete', $employeeAvailability);

        $employee = auth()->user()->employee;
        $this->employeeService->deleteAvailability($employeeAvailability, $employee);

        event(new EmployeeAvailabilityUpdated($employee));

        return response()->json([
            'success' => true,
            'message' => 'Availability deleted successfully'
        ]);
    }
}
