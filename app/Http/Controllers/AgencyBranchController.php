<?php

namespace App\Http\Controllers;

use App\Models\AgencyBranch;
use App\Http\Requests\AgencyBranch\CreateBranchRequest;
use App\Http\Requests\AgencyBranch\UpdateBranchRequest;
use App\Http\Resources\AgencyBranchCollection;
use App\Http\Resources\AgencyBranchResource;
use App\Services\AgencyBranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgencyBranchController extends Controller
{
    public function __construct(private AgencyBranchService $agencyBranchService) {}

    public function index(Request $request): AgencyBranchCollection
    {
        $this->authorize('viewAny', AgencyBranch::class);

        $query = AgencyBranch::with(['agency', 'agents'])->withCount(['agents', 'agencyEmployees', 'assignments']);

        if ($request->user()->hasRole('agency_admin') || $request->user()->hasRole('agent')) {
            $query->whereHas('agency', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id)
                    ->orWhereHas('agents', function ($q) use ($request) {
                        $q->where('user_id', $request->user()->id);
                    });
            });
        }

        if (isset($request->status)) {
            $query->where('status', $request->status);
        }

        if (isset($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('branch_code', 'like', '%' . $request->search . '%')
                    ->orWhere('city', 'like', '%' . $request->search . '%');
            });
        }

        if (isset($request->is_head_office)) {
            $query->where('is_head_office', $request->is_head_office);
        }

        if (isset($request->agency_id)) {
            $query->where('agency_id', $request->agency_id);
        }

        $agencyBranches = $query->paginate($request->per_page ?? 15);

        return new AgencyBranchCollection($agencyBranches);
    }

    public function store(CreateBranchRequest $request): JsonResponse
    {
        $agencyBranch = $this->agencyBranchService->createBranch(
            \App\Models\Agency::findOrFail($request->agency_id),
            $request->validated()
        );

        return response()->json([
            'data' => new AgencyBranchResource($agencyBranch),
            'message' => 'Branch created successfully'
        ], 201);
    }

    public function show(AgencyBranch $agencyBranch): JsonResponse
    {
        $this->authorize('view', $agencyBranch);

        $agencyBranch->load(['agency', 'agents.user', 'agencyEmployees.employee.user']);

        return response()->json([
            'data' => new AgencyBranchResource($agencyBranch)
        ]);
    }

    public function update(UpdateBranchRequest $request, AgencyBranch $agencyBranch): JsonResponse
    {
        $updatedBranch = $this->agencyBranchService->updateBranch($agencyBranch, $request->validated());

        return response()->json([
            'data' => new AgencyBranchResource($updatedBranch),
            'message' => 'Branch updated successfully'
        ]);
    }

    public function destroy(AgencyBranch $agencyBranch): JsonResponse
    {
        $this->authorize('delete', $agencyBranch);

        $deleted = $this->agencyBranchService->deleteBranch($agencyBranch);

        if (!$deleted) {
            return response()->json([
                'message' => 'Cannot delete branch with active relationships'
            ], 422);
        }

        return response()->json([
            'message' => 'Branch deleted successfully'
        ]);
    }

    public function setHeadOffice(AgencyBranch $agencyBranch): JsonResponse
    {
        $this->authorize('manageHeadOffice', $agencyBranch);

        $updatedBranch = $this->agencyBranchService->setAsHeadOffice($agencyBranch);

        return response()->json([
            'data' => new AgencyBranchResource($updatedBranch),
            'message' => 'Branch set as head office successfully'
        ]);
    }

    public function stats(AgencyBranch $agencyBranch): JsonResponse
    {
        $this->authorize('view', $agencyBranch);

        $stats = $this->agencyBranchService->getBranchStats($agencyBranch);

        return response()->json([
            'data' => $stats
        ]);
    }

    public function nearby(AgencyBranch $agencyBranch, Request $request): JsonResponse
    {
        $this->authorize('view', $agencyBranch);

        $radius = $request->get('radius', 50);
        $nearbyBranches = $this->agencyBranchService->getNearbyBranches($agencyBranch, $radius);

        return response()->json([
            'data' => $nearbyBranches
        ]);
    }
}
