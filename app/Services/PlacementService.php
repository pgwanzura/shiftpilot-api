<?php

namespace App\Services;

use App\Models\Placement;
use App\Enums\PlacementStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PlacementService
{
    public function createPlacement(array $data): Placement
    {
        return DB::transaction(function () use ($data) {
            $placement = Placement::create([
                ...$data,
                'status' => PlacementStatus::DRAFT->value,
                'created_by_id' => auth()->id(),
            ]);

            if ($placement->target_agencies === 'specific' && !empty($placement->specific_agency_ids)) {
                $this->notifyTargetedAgencies($placement);
            }

            return $placement->load(['location', 'employer', 'createdBy']);
        });
    }

    public function updatePlacement(Placement $placement, array $data): Placement
    {
        return DB::transaction(function () use ($placement, $data) {
            $placement->update($data);

            if (isset($data['status']) && $data['status'] === PlacementStatus::ACTIVE->value) {
                $this->activatePlacement($placement);
            }

            return $placement->fresh(['location', 'employer', 'createdBy']);
        });
    }

    public function getFilteredPlacements(array $filters = []): LengthAwarePaginator
    {
        $query = Placement::with([
            'location',
            'employer',
            'createdBy',
            'agencyResponses',
            'shifts'
        ])->withCount(['agencyResponses', 'shifts']);

        // Apply filters
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['experience_level'])) {
            $query->where('experience_level', $filters['experience_level']);
        }

        if (isset($filters['budget_type'])) {
            $query->where('budget_type', $filters['budget_type']);
        }

        if (isset($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (isset($filters['start_date_from'])) {
            $query->where('start_date', '>=', $filters['start_date_from']);
        }

        if (isset($filters['start_date_to'])) {
            $query->where('start_date', '<=', $filters['start_date_to']);
        }

        $user = auth()->user();
        if ($user && $user->isAgency()) {
            $user->loadMissing('agency'); // Ensure agency relationship is loaded
            if ($user->agency) { // Check if agency exists after loading
                $agencyId = $user->agency->id;
                $query->where(function ($q) use ($agencyId) {
                    $q->where('target_agencies', 'all')
                        ->orWhereJsonContains('specific_agency_ids', $agencyId);
                });
            }
        }

        // Handle employer-specific placements
        if ($user && $user->isEmployer()) {
            $user->loadMissing('employer'); // Ensure employer relationship is loaded
            if ($user->employer) { // Check if employer exists after loading
                $query->where('employer_id', $user->employer->id);
            }
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    public function activatePlacement(Placement $placement): void
    {
        if ($placement->status !== PlacementStatus::DRAFT->value) {
            throw new \Exception('Only draft placements can be activated.');
        }

        $placement->update([
            'status' => PlacementStatus::ACTIVE->value,
            'activated_at' => now(),
        ]);

        $this->notifyEligibleAgencies($placement);
    }

    public function closePlacement(Placement $placement): void
    {
        $placement->update([
            'status' => PlacementStatus::FILLED->value,
            'filled_at' => now(),
        ]);

        // Notify agencies that the position has been filled
        $this->notifyAgenciesOfClosure($placement);
    }

    public function cancelPlacement(Placement $placement, string $reason = null): void
    {
        $placement->update([
            'status' => PlacementStatus::CANCELLED->value,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        // Notify all involved agencies
        $this->notifyAgenciesOfCancellation($placement, $reason);
    }

    protected function notifyTargetedAgencies(Placement $placement): void
    {
        // Implementation for notifying specific agencies
        // This would integrate with your notification system
        // For example: Email, in-app notifications, etc.
    }

    protected function notifyEligibleAgencies(Placement $placement): void
    {
        // Implementation for notifying all eligible agencies
        // when a placement becomes active
    }

    protected function notifyAgenciesOfClosure(Placement $placement): void
    {
        // Implementation for notifying agencies when placement is filled
    }

    protected function notifyAgenciesOfCancellation(Placement $placement, ?string $reason): void
    {
        // Implementation for notifying agencies when placement is cancelled
    }

    public function getPlacementStats(): array
    {
        $employerId = auth()->user()->employer->id;

        return [
            'total' => Placement::where('employer_id', $employerId)->count(),
            'draft' => Placement::where('employer_id', $employerId)
                ->where('status', PlacementStatus::DRAFT->value)
                ->count(),
            'active' => Placement::where('employer_id', $employerId)
                ->where('status', PlacementStatus::ACTIVE->value)
                ->count(),
            'filled' => Placement::where('employer_id', $employerId)
                ->where('status', PlacementStatus::FILLED->value)
                ->count(),
            'cancelled' => Placement::where('employer_id', $employerId)
                ->where('status', PlacementStatus::CANCELLED->value)
                ->count(),
            'responses' => $this->getTotalResponses(),
        ];
    }

    private function getTotalResponses(): int
    {

        if (class_exists('App\Models\AgencyPlacementResponse')) {
            return \App\Models\AgencyPlacementResponse::count();
        }

        return Placement::withCount('agencyResponses')->get()->sum('agency_responses_count');
    }
}
