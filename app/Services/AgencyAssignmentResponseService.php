<?php

namespace App\Services;

use App\Models\AgencyAssignmentResponse;
use App\Models\Assignment;
use App\Models\Agency;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AgencyAssignmentResponseService
{
    public function getPaginatedResponses(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = AgencyAssignmentResponse::with(['assignment', 'agency']);

        // Apply filters
        if (!empty($filters['assignment_id'])) {
            $query->where('assignment_id', $filters['assignment_id']);
        }

        if (!empty($filters['agency_id'])) {
            $query->where('agency_id', $filters['agency_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('proposal_text', 'like', "%{$filters['search']}%")
                    ->orWhereHas('agency', function ($q) use ($filters) {
                        $q->where('name', 'like', "%{$filters['search']}%");
                    })
                    ->orWhereHas('assignment', function ($q) use ($filters) {
                        $q->where('title', 'like', "%{$filters['search']}%");
                    });
            });
        }

        // Apply sorting
        $sortField = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($perPage);
    }

    public function createResponse(array $data): AgencyAssignmentResponse
    {
        return DB::transaction(function () use ($data) {
            // Ensure the agency hasn't already responded to this assignment
            $existingResponse = AgencyAssignmentResponse::where([
                'assignment_id' => $data['assignment_id'],
                'agency_id' => $data['agency_id'],
            ])->first();

            if ($existingResponse) {
                throw new \Exception('Your agency has already submitted a response for this assignment.');
            }

            // Validate assignment exists and is open for responses
            $assignment = Assignment::findOrFail($data['assignment_id']);
            if (!$assignment->isAcceptingResponses()) {
                throw new \Exception('This assignment is no longer accepting responses.');
            }

            $response = AgencyAssignmentResponse::create([
                ...$data,
                'status' => AgencyAssignmentResponse::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);

            // Trigger event for notifications
            // event(new AgencyAssignmentResponseSubmitted($response));

            return $response->load(['assignment', 'agency']);
        });
    }

    public function updateResponse(AgencyAssignmentResponse $response, array $data): AgencyAssignmentResponse
    {
        return DB::transaction(function () use ($response, $data) {
            if (!$response->canBeUpdated()) {
                throw new \Exception('This response can no longer be updated.');
            }

            $response->update($data);

            return $response->load(['assignment', 'agency']);
        });
    }

    public function deleteResponse(AgencyAssignmentResponse $response): bool
    {
        if (!$response->canBeUpdated()) {
            throw new \Exception('Only submitted or reviewed responses can be deleted.');
        }

        return $response->delete();
    }

    public function acceptResponse(AgencyAssignmentResponse $response): AgencyAssignmentResponse
    {
        return DB::transaction(function () use ($response) {
            // Reject all other responses for this assignment
            AgencyAssignmentResponse::where('assignment_id', $response->assignment_id)
                ->where('id', '!=', $response->id)
                ->update([
                    'status' => AgencyAssignmentResponse::STATUS_REJECTED,
                    'rejection_reason' => 'Another agency was selected',
                    'responded_at' => now(),
                ]);

            $response->accept();

            // Update assignment status or link the accepted response
            $assignment = $response->assignment;
            $assignment->update([
                'accepted_response_id' => $response->id,
                'status' => 'assigned', // or whatever your assignment status flow is
            ]);

            // Trigger events
            // event(new AgencyAssignmentResponseAccepted($response));
            // event(new AssignmentAssigned($assignment));

            return $response->load(['assignment', 'agency']);
        });
    }

    public function rejectResponse(AgencyAssignmentResponse $response, string $reason = null): AgencyAssignmentResponse
    {
        $response->reject($reason);

        // event(new AgencyAssignmentResponseRejected($response));

        return $response->load(['assignment', 'agency']);
    }

    public function getResponseStats(int $agencyId = null): array
    {
        $query = AgencyAssignmentResponse::query();

        if ($agencyId) {
            $query->where('agency_id', $agencyId);
        }

        return [
            'total' => $query->count(),
            'submitted' => $query->where('status', AgencyAssignmentResponse::STATUS_SUBMITTED)->count(),
            'reviewed' => $query->where('status', AgencyAssignmentResponse::STATUS_REVIEWED)->count(),
            'accepted' => $query->where('status', AgencyAssignmentResponse::STATUS_ACCEPTED)->count(),
            'rejected' => $query->where('status', AgencyAssignmentResponse::STATUS_REJECTED)->count(),
        ];
    }

    public function getAssignmentResponses(int $assignmentId): array
    {
        return AgencyAssignmentResponse::with(['agency'])
            ->where('assignment_id', $assignmentId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function getAgencyResponses(int $agencyId, array $filters = []): LengthAwarePaginator
    {
        $query = AgencyAssignmentResponse::with(['assignment'])
            ->where('agency_id', $agencyId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['assignment_id'])) {
            $query->where('assignment_id', $filters['assignment_id']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }
}
