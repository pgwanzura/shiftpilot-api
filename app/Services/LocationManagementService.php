<?php

// app/Services/LocationManagementService.php

namespace App\Services;

use App\Models\Location;
use App\Models\Employer;
use App\Events\LocationCreated;
use App\Events\LocationUpdated;

class LocationManagementService
{
    public function createLocationForEmployer(Employer $employer, array $data): Location
    {
        $location = $employer->locations()->create([
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'meta' => $data['meta'] ?? null,
        ]);

        event(new LocationCreated($location));

        return $location;
    }

    public function updateLocation(Location $location, array $data): Location
    {
        $location->update($data);
        event(new LocationUpdated($location));
        return $location->fresh();
    }

    public function getEmployerLocations(Employer $employer, array $filters = [])
    {
        $query = $employer->locations();

        if (isset($filters['active_only']) && $filters['active_only']) {
            $query->whereHas('shifts', function ($q) {
                $q->whereNotIn('status', ['cancelled', 'completed']);
            });
        }

        return $query->get();
    }
}
