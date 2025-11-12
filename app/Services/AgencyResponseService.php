<?php

namespace App\Services;

use App\Models\AgencyResponse;
use Illuminate\Database\Eloquent\Collection;

class AgencyResponseService
{
    public function getAllAgencyResponses(): Collection
    {
        return AgencyResponse::all();
    }

    public function createAgencyResponse(array $data): AgencyResponse
    {
        return AgencyResponse::create($data);
    }

    public function getAgencyResponseById(string $id): ?AgencyResponse
    {
        return AgencyResponse::find($id);
    }

    public function updateAgencyResponse(AgencyResponse $agencyResponse, array $data): AgencyResponse
    {
        $agencyResponse->update($data);
        return $agencyResponse;
    }

    public function deleteAgencyResponse(AgencyResponse $agencyResponse): ?bool
    {
        return $agencyResponse->delete();
    }
}
