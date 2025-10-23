<?php

namespace App\Services;

use App\Models\Shift;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ShiftService
{
    public function getShifts(array $filters = []): LengthAwarePaginator
    {
        $query = Shift::with(['employer', 'location', 'employee.user', 'agency']);

        if (isset($filters['employer_id'])) {
            $query->where('employer_id', $filters['employer_id']);
        }

        if (isset($filters['agency_id'])) {
            $query->where('agency_id', $filters['agency_id']);
        }

        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (isset($filters['start_date'])) {
            $query->where('start_time', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('end_time', '<=', $filters['end_date']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('role_requirement', 'like', "%{$search}%")
                  ->orWhereHas('location', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('employee.user', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function createShift(array $data): Shift
    {
        return DB::transaction(function () use ($data) {
            $user = auth()->user();
            $createdByType = $user->role === 'employer_admin' ? 'employer' : 'agency';

            return Shift::create([
                'employer_id' => $data['employer_id'],
                'agency_id' => $data['agency_id'] ?? null,
                'placement_id' => $data['placement_id'] ?? null,
                'location_id' => $data['location_id'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'hourly_rate' => $data['hourly_rate'] ?? null,
                'role_requirement' => $data['role_requirement'] ?? null,
                'status' => 'open',
                'created_by_type' => $createdByType,
                'created_by_id' => $user->id,
            ]);
        });
    }

    public function updateShift(Shift $shift, array $data): Shift
    {
        $shift->update($data);
        return $shift->fresh();
    }

    public function deleteShift(Shift $shift): void
    {
        DB::transaction(function () use ($shift) {
            if ($shift->timesheet()->exists()) {
                throw new \Exception('Cannot delete shift with associated timesheet');
            }
            $shift->delete();
        });
    }

    public function cancelShift(Shift $shift): Shift
    {
        return DB::transaction(function () use ($shift) {
            $shift->update([
                'status' => 'cancelled',
            ]);

            if ($shift->timesheet) {
                $shift->timesheet->update(['status' => 'cancelled']);
            }

            return $shift->fresh();
        });
    }

    public function assignEmployee(Shift $shift, int $employeeId): Shift
    {
        return DB::transaction(function () use ($shift, $employeeId) {
            $shift->update([
                'employee_id' => $employeeId,
                'status' => 'assigned',
            ]);

            return $shift->fresh();
        });
    }

    public function completeShift(Shift $shift): Shift
    {
        return DB::transaction(function () use ($shift) {
            $shift->update([
                'status' => 'completed',
            ]);

            return $shift->fresh();
        });
    }
}
