<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Http\Requests\Assignment\StoreAssignmentRequest;
use App\Http\Requests\Assignment\UpdateAssignmentRequest;
use App\Http\Requests\Assignment\ChangeAssignmentStatusRequest;
use App\Http\Requests\Assignment\ExtendAssignmentRequest;
use App\Http\Resources\AssignmentResource;
use App\Http\Resources\AssignmentCollection;
use App\Http\Resources\AssignmentStatisticsResource;
use App\Services\AssignmentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class AssignmentController extends Controller
{
    public function __construct(
        private AssignmentService $assignmentService
    ) {}

    public function index(Request $request): AssignmentCollection
    {
        $this->authorize('viewAny', Assignment::class);

        $user = $request->user();
        
        \Log::info('Assignment index accessed', [
            'user_id' => $user->id,
            'role' => $user->role,
            'agency_id' => $user->getAgencyId(),
            'employer_id' => $user->getEmployerId()
        ]);

        $query = $this->buildBaseQuery();
        $query = $this->applyRoleScope($query, $user);
        $query = $this->applyRequestFilters($query, $request);

        $perPage = min($request->query('per_page', 20), 100);
        $assignments = $query->latest()->paginate($perPage);

        \Log::info('Assignment query results', [
            'user_id' => $user->id,
            'total_results' => $assignments->total(),
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);

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
            'shifts' => fn($query) => $query->orderBy('start_time'),
            'shifts.timesheets'
        ]);

        return new AssignmentResource($assignment);
    }

    public function update(UpdateAssignmentRequest $request, Assignment $assignment): JsonResponse
    {
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

    public function changeStatus(ChangeAssignmentStatusRequest $request, Assignment $assignment): JsonResponse
    {
        $updatedAssignment = $this->assignmentService->changeStatus(
            $assignment,
            $request->status,
            $request->reason
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

    public function extend(ExtendAssignmentRequest $request, Assignment $assignment): JsonResponse
    {
        $updatedAssignment = $this->assignmentService->extendAssignment(
            $assignment,
            $request->end_date,
            $request->reason
        );

        return response()->json([
            'message' => 'Assignment extended successfully',
            'data' => new AssignmentResource($updatedAssignment)
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Assignment::class);

        $filters = $request->only(['start_date', 'end_date', 'agency_id', 'employer_id']);
        $stats = $this->assignmentService->getStatistics($request->user(), $filters);

        return response()->json([
            'data' => new AssignmentStatisticsResource($stats)
        ]);
    }

    public function myAssignments(Request $request): AssignmentCollection
    {
        $user = $request->user();
        $query = $this->buildBaseQuery();
        $query = $this->applyUserAssignmentScope($query, $user);
        $query = $this->applyRequestFilters($query, $request);

        $perPage = min($request->query('per_page', 20), 100);
        $assignments = $query->latest()->paginate($perPage);

        return new AssignmentCollection($assignments);
    }

    public function debug(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $debugInfo = [
            'user' => [
                'id' => $user->id,
                'role' => $user->role,
                'is_super_admin' => $user->isSuperAdmin(),
                'is_agency_admin' => $user->isAgencyAdmin(),
                'is_agent' => $user->isAgent(),
                'is_employer_admin' => $user->isEmployerAdmin(),
                'is_contact' => $user->isContact(),
                'is_employee' => $user->isEmployee(),
                'agency_id_from_method' => $user->getAgencyId(),
                'employer_id_from_method' => $user->getEmployerId(),
                'agent_agency_id' => $user->agent?->agency_id,
                'agency_id' => $user->agency?->id,
            ],
            'database' => [
                'total_assignments' => Assignment::count(),
                'assignments_with_agency_1' => Assignment::whereHas('agencyEmployee', fn($q) => $q->where('agency_id', 1))->count(),
                'assignments_with_employer_1' => Assignment::whereHas('contract', fn($q) => $q->where('employer_id', 1))->count(),
            ]
        ];

        // Test different scoping scenarios
        $debugInfo['scoping_tests'] = [
            'super_admin_scope' => $user->isSuperAdmin() ? Assignment::count() : 'N/A',
            'agency_scope' => $user->isAgencyAdmin() || $user->isAgent() ? 
                Assignment::whereHas('agencyEmployee', fn($q) => $q->where('agency_id', $this->getUserAgencyId($user)))->count() : 'N/A',
            'employer_scope' => $user->isEmployerAdmin() || $user->isContact() ? 
                Assignment::whereHas('contract', fn($q) => $q->where('employer_id', $user->getEmployerId()))->count() : 'N/A',
            'employee_scope' => $user->isEmployee() ? 
                Assignment::whereHas('agencyEmployee.employee', fn($q) => $q->where('user_id', $user->id))->count() : 'N/A',
        ];

        return response()->json($debugInfo);
    }

    private function buildBaseQuery(): Builder
    {
        return Assignment::with([
            'contract.employer',
            'contract.agency',
            'agencyEmployee.employee.user',
            'agencyEmployee.agency',
            'location',
            'shiftRequest',
            'agencyResponse',
            'createdBy'
        ]);
    }

    private function applyRoleScope(Builder $query, $user): Builder
    {
        if ($user->isSuperAdmin()) {
            \Log::info('Applying super admin scope - no restrictions');
            return $query;
        }

        if ($user->isAgencyAdmin() || $user->isAgent()) {
            $agencyId = $this->getUserAgencyId($user);
            
            \Log::info('Agency user scope check', [
                'user_id' => $user->id,
                'agency_id' => $agencyId,
                'is_agency_admin' => $user->isAgencyAdmin(),
                'is_agent' => $user->isAgent()
            ]);

            if (!$agencyId) {
                \Log::warning('Agency user has no agency ID', ['user_id' => $user->id]);
                return $query->whereRaw('0 = 1');
            }

            \Log::info('Applying agency scope', ['agency_id' => $agencyId]);
            return $query->whereHas('agencyEmployee', function($q) use ($agencyId) {
                $q->where('agency_id', $agencyId);
            });
        }

        if ($user->isEmployerAdmin() || $user->isContact()) {
            $employerId = $user->getEmployerId();
            
            \Log::info('Employer user scope check', [
                'user_id' => $user->id,
                'employer_id' => $employerId,
                'is_employer_admin' => $user->isEmployerAdmin(),
                'is_contact' => $user->isContact()
            ]);

            if (!$employerId) {
                \Log::warning('Employer user has no employer ID', ['user_id' => $user->id]);
                return $query->whereRaw('0 = 1');
            }

            \Log::info('Applying employer scope', ['employer_id' => $employerId]);
            return $query->whereHas('contract', function($q) use ($employerId) {
                $q->where('employer_id', $employerId);
            });
        }

        if ($user->isEmployee()) {
            \Log::info('Applying employee scope', ['user_id' => $user->id]);
            return $query->whereHas('agencyEmployee.employee', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        \Log::warning('User has no valid role for assignment access', ['user_id' => $user->id, 'role' => $user->role]);
        return $query->whereRaw('0 = 1');
    }

    private function applyUserAssignmentScope(Builder $query, $user): Builder
    {
        if ($user->isEmployee()) {
            return $query->whereHas('agencyEmployee.employee', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        if ($user->isAgencyAdmin() || $user->isAgent()) {
            $agencyId = $this->getUserAgencyId($user);
            return $agencyId ? 
                $query->whereHas('agencyEmployee', function($q) use ($agencyId) {
                    $q->where('agency_id', $agencyId);
                }) : 
                $query->whereRaw('0 = 1');
        }

        if ($user->isEmployerAdmin() || $user->isContact()) {
            $employerId = $user->getEmployerId();
            return $employerId ?
                $query->whereHas('contract', function($q) use ($employerId) {
                    $q->where('employer_id', $employerId);
                }) :
                $query->whereRaw('0 = 1');
        }

        return $query;
    }

    private function applyRequestFilters(Builder $query, Request $request): Builder
    {
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('assignment_type')) {
            $query->where('assignment_type', $request->assignment_type);
        }

        $user = $request->user();
        
        if ($request->filled('agency_id') && ($user->isAgencyAdmin() || $user->isAgent())) {
            $query->whereHas('agencyEmployee', function($q) use ($request) {
                $q->where('agency_id', $request->agency_id);
            });
        }

        if ($request->filled('employer_id') && ($user->isEmployerAdmin() || $user->isContact())) {
            $query->whereHas('contract', function($q) use ($request) {
                $q->where('employer_id', $request->employer_id);
            });
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('start_date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->where('start_date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->where(function($q) use ($request) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '<=', $request->end_date);
            });
        }

        if ($request->filled('search')) {
            $query->where('role', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        return $query;
    }

    private function getUserAgencyId($user): ?int
    {
        if ($user->isAgencyAdmin()) {
            return $user->agency?->id;
        }

        if ($user->isAgent()) {
            return $user->agent?->agency_id;
        }

        return null;
    }
}