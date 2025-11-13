<?php

namespace App\Http\Controllers;

use App\Events\EmployeePreference\EmployeePreferencesCreated;
use App\Events\EmployeePreference\EmployeePreferencesUpdated;
use App\Events\EmployeePreference\EmployeePreferencesDeleted;
use App\Http\Requests\EmployeePreference\CreateEmployeePreferencesRequest;
use App\Http\Requests\EmployeePreference\UpdateEmployeePreferencesRequest;
use App\Http\Resources\EmployeePreferencesResource;
use App\Models\Employee;
use App\Models\EmployeePreference;
use App\Services\EmployeeMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class EmployeePreferencesController extends Controller
{
    protected $matchingService;

    public function __construct(EmployeeMatchingService $matchingService)
    {
        $this->matchingService = $matchingService;

        $this->authorizeResource(EmployeePreference::class, 'preference');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = EmployeePreference::with(['employee.user']);

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('has_auto_accept')) {
            $query->where('auto_accept_offers', $request->boolean('has_auto_accept'));
        }

        $preferences = $query->paginate($request->get('per_page', 15));

        return EmployeePreferencesResource::collection($preferences);
    }

    public function show(EmployeePreference $preference): EmployeePreferencesResource
    {
        $preference->load(['employee.user', 'employee.agencies']);

        return new EmployeePreferencesResource($preference);
    }

    public function store(CreateEmployeePreferencesRequest $request): EmployeePreferencesResource
    {
        $preferences = DB::transaction(function () use ($request) {
            $existing = EmployeePreference::where('employee_id', $request->employee_id)->first();

            if ($existing) {
                abort(422, 'Employee already has preferences record');
            }

            $preferences = EmployeePreference::create($request->validated());

            event(new EmployeePreferencesCreated($preferences));

            return $preferences;
        });

        return new EmployeePreferencesResource($preferences->load('employee'));
    }

    public function update(UpdateEmployeePreferencesRequest $request, EmployeePreference $preference): EmployeePreferencesResource
    {
        $original = $preference->getOriginal();

        $preference = DB::transaction(function () use ($request, $preference, $original) {
            $preference->update($request->validated());

            $changes = $preference->getChanges();

            if (!empty($changes)) {
                event(new EmployeePreferencesUpdated($preference, $original, $changes));
            }

            return $preference->fresh();
        });

        return new EmployeePreferencesResource($preference->load('employee'));
    }


    public function destroy(EmployeePreference $preference): JsonResponse
    {
        DB::transaction(function () use ($preference) {
            $preferenceData = $preference->toArray();

            $preference->delete();

            event(new EmployeePreferencesDeleted($preference));
        });

        return response()->json(['message' => 'Employee preferences deleted successfully']);
    }

    public function getByEmployee(Employee $employee): EmployeePreferencesResource
    {
        $this->authorize('view', $employee);

        $preferences = EmployeePreference::where('employee_id', $employee->id)->firstOrFail();

        return new EmployeePreferencesResource($preferences->load('employee.user'));
    }

    public function updateByEmployee(Request $request, Employee $employee): EmployeePreferencesResource
    {
        $this->authorize('update', $employee);

        $request->validate([
            'preferred_shift_types' => 'nullable|array',
            'preferred_locations' => 'nullable|array',
            'max_travel_distance_km' => 'nullable|integer|min:1|max:500',
            'min_hourly_rate' => 'nullable|numeric|min:0',
            'preferred_days' => 'nullable|array',
            'auto_accept_offers' => 'boolean',
        ]);

        $preferences = EmployeePreference::where('employee_id', $employee->id)->firstOrFail();

        $original = $preferences->getOriginal();

        $preferences->update($request->only([
            'preferred_shift_types',
            'preferred_locations',
            'max_travel_distance_km',
            'min_hourly_rate',
            'preferred_days',
            'auto_accept_offers',
        ]));

        $changes = $preferences->getChanges();

        if (!empty($changes)) {
            event(new EmployeePreferencesUpdated($preferences, $original, $changes));
        }

        return new EmployeePreferencesResource($preferences->fresh()->load('employee.user'));
    }

    public function getMatchingShifts(EmployeePreference $preference): JsonResponse
    {
        $this->authorize('view', $preference);

        $matchingShifts = $this->matchingService->findMatchingShifts($preference);

        return response()->json([
            'data' => $matchingShifts,
            'meta' => [
                'total_matches' => count($matchingShifts),
                'preferences_used' => [
                    'locations' => $preference->preferred_locations,
                    'max_travel_distance' => $preference->max_travel_distance_km,
                    'min_rate' => $preference->min_hourly_rate,
                    'preferred_days' => $preference->preferred_days,
                ]
            ]
        ]);
    }
}
