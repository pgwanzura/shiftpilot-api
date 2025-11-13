<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\AgencyBranch;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AgencyBranchService
{
    public function createBranch(Agency $agency, array $data): AgencyBranch
    {
        return DB::transaction(function () use ($agency, $data) {
            if (isset($data['is_head_office']) && $data['is_head_office']) {
                AgencyBranch::where('agency_id', $agency->id)
                    ->where('is_head_office', true)
                    ->update(['is_head_office' => false]);
            }

            return AgencyBranch::create(array_merge($data, ['agency_id' => $agency->id]));
        });
    }

    public function updateBranch(AgencyBranch $branch, array $data): AgencyBranch
    {
        return DB::transaction(function () use ($branch, $data) {
            if (isset($data['is_head_office']) && $data['is_head_office'] && !$branch->is_head_office) {
                AgencyBranch::where('agency_id', $branch->agency_id)
                    ->where('id', '!=', $branch->id)
                    ->where('is_head_office', true)
                    ->update(['is_head_office' => false]);
            }

            $branch->update($data);
            return $branch->fresh();
        });
    }

    public function deleteBranch(AgencyBranch $branch): bool
    {
        if ($branch->is_head_office) {
            throw new \Exception('Cannot delete head office branch');
        }

        return DB::transaction(function () use ($branch) {
            if (
                $branch->agents()->exists() ||
                $branch->agencyEmployees()->exists() ||
                $branch->assignments()->exists()
            ) {
                return false;
            }

            return $branch->delete();
        });
    }

    public function setAsHeadOffice(AgencyBranch $branch): AgencyBranch
    {
        return DB::transaction(function () use ($branch) {
            AgencyBranch::where('agency_id', $branch->agency_id)
                ->where('is_head_office', true)
                ->update(['is_head_office' => false]);

            $branch->update(['is_head_office' => true]);
            return $branch->fresh();
        });
    }

    public function getBranchesWithFilters(Agency $agency, array $filters): LengthAwarePaginator
    {
        $query = $agency->branches()->withCount(['agents', 'agencyEmployees', 'assignments']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('branch_code', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('city', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (isset($filters['is_head_office'])) {
            $query->where('is_head_office', $filters['is_head_office']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getBranchStats(AgencyBranch $branch): array
    {
        return [
            'total_agents' => $branch->agents()->count(),
            'total_employees' => $branch->agencyEmployees()->where('status', 'active')->count(),
            'active_assignments' => $branch->assignments()->where('status', 'active')->count(),
            'pending_payroll' => $branch->payrolls()->where('status', 'pending')->count(),
        ];
    }

    public function getNearbyBranches(AgencyBranch $branch, float $radiusKm = 50): array
    {
        if (!$branch->hasGeoLocation()) {
            return [];
        }

        $earthRadius = 6371;

        return AgencyBranch::select('*')
            ->selectRaw(
                "(? * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance",
                [$earthRadius, $branch->latitude, $branch->longitude, $branch->latitude]
            )
            ->where('agency_id', $branch->agency_id)
            ->where('id', '!=', $branch->id)
            ->where('status', 'active')
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance')
            ->get()
            ->toArray();
    }
}
