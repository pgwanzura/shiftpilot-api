<?php

namespace App\Services;

use App\Models\TimeOffRequest;
use App\Models\Employee;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TimeOffService
{
    public function getTimeOffRequests(array $filters = []): LengthAwarePaginator
    {
        $query = TimeOffRequest::with(['employee.user', 'approvedBy']);

        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (isset($filters['agency_id'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('agency_id', $filters['agency_id']);
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['start_date'])) {
            $query->where('start_date', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('end_date', '<=', $filters['end_date']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhereHas('employee.user', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function createTimeOffRequest(array $data): TimeOffRequest
    {
        return DB::transaction(function () use ($data) {
            return TimeOffRequest::create([
                'employee_id' => $data['employee_id'],
                'type' => $data['type'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'reason' => $data['reason'] ?? null,
                'status' => 'pending',
            ]);
        });
    }

    public function updateTimeOffRequest(TimeOffRequest $timeOffRequest, array $data): TimeOffRequest
    {
        $timeOffRequest->update($data);
        return $timeOffRequest->fresh();
    }

    public function deleteTimeOffRequest(TimeOffRequest $timeOffRequest): void
    {
        $timeOffRequest->delete();
    }

    public function approveTimeOffRequest(TimeOffRequest $timeOffRequest): TimeOffRequest
    {
        return DB::transaction(function () use ($timeOffRequest) {
            $timeOffRequest->update([
                'status' => 'approved',
                'approved_by_id' => auth()->id(),
                'approved_at' => now(),
            ]);

            return $timeOffRequest->fresh();
        });
    }

    public function rejectTimeOffRequest(TimeOffRequest $timeOffRequest): TimeOffRequest
    {
        return DB::transaction(function () use ($timeOffRequest) {
            $timeOffRequest->update([
                'status' => 'rejected',
                'approved_by_id' => auth()->id(),
                'approved_at' => now(),
            ]);

            return $timeOffRequest->fresh();
        });
    }

    public function cancelTimeOffRequest(TimeOffRequest $timeOffRequest): TimeOffRequest
    {
        return DB::transaction(function () use ($timeOffRequest) {
            $timeOffRequest->update([
                'status' => 'cancelled',
            ]);

            return $timeOffRequest->fresh();
        });
    }

    public function checkConflicts(Employee $employee, $startDate, $endDate, $startTime = null, $endTime = null)
    {
        $query = TimeOffRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                      $q2->where('start_date', '<=', $startDate)
                         ->where('end_date', '>=', $endDate);
                  });
            });

        if ($startTime && $endTime) {
            $query->where(function ($q) use ($startTime, $endTime) {
                $q->whereNotNull('start_time')
                  ->whereNotNull('end_time')
                  ->where(function ($q2) use ($startTime, $endTime) {
                      $q2->whereBetween('start_time', [$startTime, $endTime])
                         ->orWhereBetween('end_time', [$startTime, $endTime])
                         ->orWhere(function ($q3) use ($startTime, $endTime) {
                             $q3->where('start_time', '<=', $startTime)
                                ->where('end_time', '>=', $endTime);
                         });
                  });
            });
        }

        return $query->get();
    }
}
