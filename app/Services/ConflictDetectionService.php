<?php

namespace App\Services;

use App\Models\Employee;

class ConflictDetectionService
{
    public function findConflictingShifts(Employee $employee, string $startDate, string $endDate)
    {
        return $employee->shifts()
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_time', [$startDate, $endDate])
                    ->orWhereBetween('end_time', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_time', '<=', $startDate)
                            ->where('end_time', '>=', $endDate);
                    });
            })
            ->whereIn('status', ['assigned', 'offered'])
            ->with('employer', 'location')
            ->get();
    }
}
