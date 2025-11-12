<?php

namespace App\Services;

use App\Models\AgencyPlacementResponse;
use Illuminate\Database\Eloquent\Collection;

class AgencyPlacementResponseService
{
    public function getAllAgencyPlacementResponses(): Collection
    {
        return AgencyPlacementResponse::all();
    }

    public function createAgencyPlacementResponse(array $data): AgencyPlacementResponse
    {
        return AgencyPlacementResponse::create($data);
    }

    public function getAgencyPlacementResponseById(string $id): ?AgencyPlacementResponse
    {
        return AgencyPlacementResponse::find($id);
    }

    public function updateAgencyPlacementResponse(AgencyPlacementResponse $agencyPlacementResponse, array $data): AgencyPlacementResponse
    {
        $agencyPlacementResponse->update($data);
        return $agencyPlacementResponse;
    }

    public function deleteAgencyPlacementResponse(AgencyPlacementResponse $agencyPlacementResponse): ?bool
    {
        return $agencyPlacementResponse->delete();
    }
}
