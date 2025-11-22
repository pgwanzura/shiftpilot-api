<?php

namespace App\Http\Controllers;

use App\Enums\AssignmentStatus;
use App\Http\Requests\Assignment\ChangeAssignmentStatusRequest;
use App\Http\Requests\Assignment\ExtendAssignmentRequest;
use App\Http\Requests\Assignment\StoreAssignmentRequest;
use App\Http\Requests\Assignment\UpdateAssignmentRequest;
use App\Http\Resources\AssignmentCollection;
use App\Http\Resources\AssignmentResource;
use App\Http\Resources\AssignmentStatisticsResource;
use App\Models\Assignment;
use App\Services\AssignmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly AssignmentService $assignmentService
    ) {}

    public function index(Request $request): AssignmentCollection
    {
        $this->authorize('viewAny', Assignment::class);

        $validated = $request->validate([
            'per_page' => 'integer|min:1|max:100',
            'status' => 'string|in:pending,active,completed,cancelled,suspended',
            'assignment_type' => 'string|in:standard,direct',
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
            'search' => 'string|max:255',
            'location_id' => 'integer|exists:locations,id',
        ]);

        $query = $this->buildScopedQuery($request->user());
        $query = $this->applyFilters($query, $validated);

        $perPage = $validated['per_page'] ?? 20;
        $assignments = $query->latest()->paginate($perPage);

        return new AssignmentCollection($assignments);
    }

    public function store(StoreAssignmentRequest $request): JsonResponse
    {
        $assignment = $this->assignmentService->createAssignment(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'message' => 'Assignment created successfully',
            'data' => new AssignmentResource($assignment)
        ], Response::HTTP_CREATED);
    }

    public function show(Assignment $assignment): AssignmentResource
    {
        $this->authorize('view', $assignment);

        $assignment->load([
            'contract.employer',
            'contract.agency',
            'agencyEmployee.employee.user',
            'agencyEmployee.agency',
            'location',
            'shiftRequest',
            'agencyResponse',
            'createdBy',
            'shifts' => fn(Builder $query) => $query->orderBy('start_time'),
            'shifts.timesheets'
        ]);

        return new AssignmentResource($assignment);
    }

    public function update(
        UpdateAssignmentRequest $request,
        Assignment $assignment
    ): JsonResponse {
        $updatedAssignment = $this->assignmentService->updateAssignment(
            $assignment,
            $request->validated()
        );

        return response()->json([
            'message' => 'Assignment updated successfully',
            'data' => new AssignmentResource($updatedAssignment)
        ]);
    }

    public function destroy(Assignment $assignment): JsonResponse
    {
        $this->authorize('delete', $assignment);

        if ($assignment->shifts()->exists()) {
            throw ValidationException::withMessages([
                'assignment' => 'Cannot delete assignment with existing shifts'
            ]);
        }

        $assignment->delete();

        return response()->json([
            'message' => 'Assignment deleted successfully'
        ]);
    }

    public function changeStatus(
        ChangeAssignmentStatusRequest $request,
        Assignment $assignment
    ): JsonResponse {
        $updatedAssignment = $this->assignmentService->changeStatus(
            $assignment,
            $request->validated('status'),
            $request->validated('reason')
        );

        return response()->json([
            'message' => 'Assignment status updated successfully',
            'data' => new AssignmentResource($updatedAssignment)
        ]);
    }

    public function complete(Request $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('complete', $assignment);

        $validated = $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $updatedAssignment = $this->assignmentService->completeAssignment(
            $assignment,
            $validated['reason']
        );

        return response()->json([
            'message' => 'Assignment completed successfully',
            'data' => new AssignmentResource($updatedAssignment)
        ]);
    }

    public function suspend(Request $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('suspend', $assignment);

        $validated = $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $updatedAssignment = $this->assignmentService->suspendAssignment(
            $assignment,
            $validated['reason']
        );

        return response()->json([
            'message' => 'Assignment suspended successfully',
            'data' => new AssignmentResource($updatedAssignment)
        ]);
    }

    public function reactivate(Request $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('reactivate', $assignment);

        $validated = $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $updatedAssignment = $this->assignmentService->reactivateAssignment(
            $assignment,
            $validated['reason']
        );

        return response()->json([
            'message' => 'Assignment reactivated successfully',
            'data' => new AssignmentResource($updatedAssignment)
        ]);
    }

    public function cancel(Request $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('cancel', $assignment);

        $validated = $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $updatedAssignment = $this->assignmentService->cancelAssignment(
            $assignment,
            $validated['reason']
        );

        return response()->json([
            'message' => 'Assignment cancelled successfully',
            'data' => new AssignmentResource($updatedAssignment)
        ]);
    }

    public function extend(
        ExtendAssignmentRequest $request,
        Assignment $assignment
    ): JsonResponse {
        $updatedAssignment = $this->assignmentService->extendAssignment(
            $assignment,
            $request->validated('end_date'),
            $request->validated('reason')
        );

        return response()->json([
            'message' => 'Assignment extended successfully',
            'data' => new AssignmentResource($updatedAssignment)
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Assignment::class);

        $validated = $request->validate([
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
            'agency_id' => 'integer|exists:agencies,id',
            'employer_id' => 'integer|exists:employers,id',
        ]);

        $stats = $this->assignmentService->getStatistics(
            $request->user(),
            $validated
        );

        return response()->json([
            'data' => new AssignmentStatisticsResource($stats)
        ]);
    }

    public function myAssignments(Request $request): AssignmentCollection
    {
        $validated = $request->validate([
            'per_page' => 'integer|min:1|max:100',
            'status' => 'string|in:pending,active,completed,cancelled,suspended',
        ]);

        $query = $this->buildUserAssignmentQuery($request->user());
        $query = $this->applyFilters($query, $validated);

        $perPage = $validated['per_page'] ?? 20;
        $assignments = $query->latest()->paginate($perPage);

        return new AssignmentCollection($assignments);
    }

    protected function buildScopedQuery($user): Builder
    {
        $query = Assignment::query();

        if ($user->isSuperAdmin()) {
            return $query->with([
                'contract.employer',
                'contract.agency',
                'agencyEmployee.employee.user',
                'agencyEmployee.agency',
                'location',
                'createdBy'
            ]);
        }

        if ($user->isAgency()) {
            $agencyId = $user->getAgencyId();
            if (!$agencyId) {
                return $query->whereRaw('1 = 0');
            }

            return $query->forAgency($agencyId)->with([
                'contract.employer',
                'agencyEmployee.employee.user',
                'agencyEmployee.agency',
                'location',
                'createdBy'
            ]);
        }

        if ($user->isEmployer()) {
            $employerId = $user->getEmployerId();
            if (!$employerId) {
                return $query->whereRaw('1 = 0');
            }

            return $query->forEmployer($employerId)->with([
                'contract.employer',
                'agencyEmployee.employee.user',
                'location',
                'createdBy'
            ]);
        }

        if ($user->isEmployee()) {
            return $query->whereHas(
                'agencyEmployee.employee',
                fn(Builder $q) =>
                $q->where('user_id', $user->id)
            )->with([
                'contract.employer',
                'agencyEmployee.agency',
                'location'
            ]);
        }

        return $query->whereRaw('1 = 0');
    }

    protected function buildUserAssignmentQuery($user): Builder
    {
        $query = Assignment::with([
            'contract.employer',
            'agencyEmployee.employee.user',
            'location',
        ]);

        if ($user->isEmployee()) {
            return $query->whereHas(
                'agencyEmployee.employee',
                fn(Builder $q) =>
                $q->where('user_id', $user->id)
            );
        }

        if ($user->isAgency()) {
            $agencyId = $user->getAgencyId();
            return $agencyId ? $query->forAgency($agencyId) : $query->whereRaw('1 = 0');
        }

        if ($user->isEmployer()) {
            $employerId = $user->getEmployerId();
            return $employerId ? $query->forEmployer($employerId) : $query->whereRaw('1 = 0');
        }

        return $query->whereRaw('1 = 0');
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['status'])) {
            $query->where('status', AssignmentStatus::from($filters['status']));
        }

        if (isset($filters['assignment_type'])) {
            $query->where('assignment_type', $filters['assignment_type']);
        }

        if (isset($filters['start_date'])) {
            if (isset($filters['end_date'])) {
                $query->dateRange($filters['start_date'], $filters['end_date']);
            } else {
                $query->where('start_date', '>=', $filters['start_date']);
            }
        }

        if (isset($filters['search'])) {
            $query->where('role', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        return $query;
    }
}
