<?php

namespace App\Services;

use App\Models\AgencyEmployee;
use Illuminate\Database\Eloquent\Collection;

class AgencyEmployeeService
{
    public function getAllAgencyEmployees(): Collection
    {
        return AgencyEmployee::all();
    }

    public function createAgencyEmployee(array $data): AgencyEmployee
    {
        return AgencyEmployee::create($data);
    }

    public function getAgencyEmployeeById(string $id): ?AgencyEmployee
    {
        return AgencyEmployee::find($id);
    }

    public function updateAgencyEmployee(AgencyEmployee $agencyEmployee, array $data): AgencyEmployee
    {
        $agencyEmployee->update($data);
        return $agencyEmployee;
    }

    public function deleteAgencyEmployee(AgencyEmployee $agencyEmployee): ?bool
    {
        return $agencyEmployee->delete();
    }
}
