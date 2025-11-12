<?php
// app/Http/Controllers/AssignmentController.php

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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AssignmentController extends Controller
{
    public function __construct(
        private AssignmentService $assignmentService
    ) {}

    public function index(Request $request): AssignmentCollection
    {
        $this->authorize('viewAny', Assignment::class);

        $query = Assignment::with([
            'contract.employer',
            'contract.agency',
            'agencyEmployee.employee.user',
            'agencyEmployee.agency',
            'location',
            'shiftRequest',
            'agencyResponse',
            'createdBy'
        ]);

        $this->applyFilters($query, $request);

        $assignments = $query->latest()->paginate($request->per_page ?? 20);

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

    /**
     * Display the specified resource.
     */
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
            'shifts' => function ($query) {
                $query->orderBy('start_time');
            },
            'shifts.timesheets'
        ]);

        return new AssignmentResource($assignment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAssignmentRequest $request, Assignment $assignment): JsonResponse
    {
        $assignment = $this->assignmentService->updateAssignment(
            $assignment,
            $request->validated()
        );

        return response()->json([
            'message' => 'Assignment updated successfully',
            'data' => new AssignmentResource($assignment)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Assignment $assignment): JsonResponse
    {
        $this->authorize('delete', $assignment);

        // Check if assignment has shifts
        if ($assignment->shifts()->exists()) {
            return response()->json([
                'message' => 'Cannot delete assignment with existing shifts'
            ], Response::HTTP_CONFLICT);
        }

        $assignment->delete();

        return response()->json([
            'message' => 'Assignment deleted successfully'
        ]);
    }

    /**
     * Change assignment status
     */
    public function changeStatus(ChangeAssignmentStatusRequest $request, Assignment $assignment): JsonResponse
    {
        $assignment = $this->assignmentService->changeStatus(
            $assignment,
            $request->status,
            $request->reason
        );

        return response()->json([
            'message' => 'Assignment status updated successfully',
            'data' => new AssignmentResource($assignment)
        ]);
    }

    /**
     * Complete assignment
     */
    public function complete(Request $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('complete', $assignment);

        $assignment = $this->assignmentService->completeAssignment(
            $assignment,
            $request->reason
        );

        return response()->json([
            'message' => 'Assignment completed successfully',
            'data' => new AssignmentResource($assignment)
        ]);
    }

    /**
     * Suspend assignment
     */
    public function suspend(Request $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('suspend', $assignment);

        $request->validate(['reason' => 'required|string|max:500']);

        $assignment = $this->assignmentService->suspendAssignment(
            $assignment,
            $request->reason
        );

        return response()->json([
            'message' => 'Assignment suspended successfully',
            'data' => new AssignmentResource($assignment)
        ]);
    }

    /**
     * Reactivate assignment
     */
    public function reactivate(Request $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('reactivate', $assignment);

        $request->validate(['reason' => 'required|string|max:500']);

        $assignment = $this->assignmentService->reactivateAssignment(
            $assignment,
            $request->reason
        );

        return response()->json([
            'message' => 'Assignment reactivated successfully',
            'data' => new AssignmentResource($assignment)
        ]);
    }

    /**
     * Cancel assignment
     */
    public function cancel(Request $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('cancel', $assignment);

        $request->validate(['reason' => 'required|string|max:500']);

        $assignment = $this->assignmentService->cancelAssignment(
            $assignment,
            $request->reason
        );

        return response()->json([
            'message' => 'Assignment cancelled successfully',
            'data' => new AssignmentResource($assignment)
        ]);
    }

    public function extend(ExtendAssignmentRequest $request, Assignment $assignment): JsonResponse
    {
        $assignment = $this->assignmentService->extendAssignment(
            $assignment,
            $request->end_date,
            $request->reason
        );

        return response()->json([
            'message' => 'Assignment extended successfully',
            'data' => new AssignmentResource($assignment)
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Assignment::class);

        $filters = $request->only(['start_date', 'end_date', 'agency_id', 'employer_id']);

        $stats = $this->assignmentService->getStatistics(
            $request->user(),
            $filters
        );

        return response()->json([
            'data' => new AssignmentStatisticsResource($stats)
        ]);
    }

    public function myAssignments(Request $request): AssignmentCollection
    {
        $user = $request->user();
        $query = Assignment::with([
            'contract.employer',
            'contract.agency',
            'agencyEmployee.agency',
            'location',
            'shifts'
        ]);

        if ($user->isEmployee()) {
            $query->whereHas('agencyEmployee.employee', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($user->isAgency()) {
            $query->whereHas('agencyEmployee', function ($q) use ($user) {
                $q->where('agency_id', $user->getAgencyId());
            });
        } elseif ($user->isEmployer()) {
            $query->whereHas('contract', function ($q) use ($user) {
                $q->where('employer_id', $user->getEmployerId());
            });
        }

        $this->applyFilters($query, $request);

        $assignments = $query->latest()->paginate($request->per_page ?? 20);

        return new AssignmentCollection($assignments);
    }

    private function applyFilters($query, Request $request): void
    {

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('assignment_type')) {
            $query->where('assignment_type', $request->assignment_type);
        }

        if ($request->has('agency_id') && $request->user()->isAgency()) {
            $query->whereHas('agencyEmployee', function ($q) use ($request) {
                $q->where('agency_id', $request->agency_id);
            });
        }

        if ($request->has('employer_id') && $request->user()->isEmployer()) {
            $query->whereHas('contract', function ($q) use ($request) {
                $q->where('employer_id', $request->employer_id);
            });
        }

        if ($request->has(['start_date', 'end_date'])) {
            $query->dateRange($request->start_date, $request->end_date);
        } elseif ($request->has('start_date')) {
            $query->where('start_date', '>=', $request->start_date);
        } elseif ($request->has('end_date')) {
            $query->where(function ($q) use ($request) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '<=', $request->end_date);
            });
        }

        if ($request->has('search')) {
            $query->where('role', 'like', '%' . $request->search . '%');
        }

        if ($request->has('location_id')) {
            $query->where('location_id', $request->location_id);
        }
    }
}
