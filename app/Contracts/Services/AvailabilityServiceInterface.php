<?php

namespace App\Contracts;

use App\Models\Employee;
use App\Models\TimeOffRequest;
use Illuminate\Database\Eloquent\Collection;

interface AvailabilityServiceInterface
{
    public function updateAvailability(Employee $employee, array $availabilityData): void;
    public function requestTimeOff(Employee $employee, array $timeOffData): TimeOffRequest;
    public function approveTimeOff(TimeOffRequest $timeOffRequest, int $approvedById): void;
    public function checkAvailabilityConflicts(Employee $employee, string $startDate, string $endDate): Collection;
    public function findAvailableEmployees(\DateTime $start, \DateTime $end): Collection;
}
