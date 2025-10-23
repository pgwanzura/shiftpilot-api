<?php

namespace App\Services;

use App\Models\Employer;
use App\Models\Location;
use Illuminate\Pagination\LengthAwarePaginator;

class LocationService
{
    public function getLocations(array $filters = []): LengthAwarePaginator
    {
        $query = Location::with(['employer']);

        if (isset($filters['employer_id'])) {
            $query->where('employer_id', $filters['employer_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function createLocation(Employer $employer, array $data): Location
    {
        return Location::create(array_merge($data, [
            'employer_id' => $employer->id,
        ]));
    }

    public function updateLocation(Location $location, array $data): Location
    {
        $location->update($data);
        return $location->fresh();
    }

    public function deleteLocation(Location $location): void
    {
        $location->delete();
    }
}
