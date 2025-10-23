<?php

namespace App\Services;

use App\Models\Timesheet;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TimesheetService
{
    public function getTimesheets(array $filters = []): LengthAwarePaginator
    {
        $query = Timesheet::with(['shift', 'employee.user', 'agencyApprovedBy', 'approvedByContact']);

        if (isset($filters['shift_id'])) {
            $query->where('shift_id', $filters['shift_id']);
        }

        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (isset($filters['agency_id'])) {
            $query->whereHas('shift', function ($q) use ($filters) {
                $q->where('agency_id', $filters['agency_id']);
            });
        }

        if (isset($filters['employer_id'])) {
            $query->whereHas('shift', function ($q) use ($filters) {
                $q->where('employer_id', $filters['employer_id']);
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhereHas('employee.user', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function createTimesheet(array $data): Timesheet
    {
        return DB::transaction(function () use ($data) {
            return Timesheet::create([
                'shift_id' => $data['shift_id'],
                'employee_id' => $data['employee_id'],
                'clock_in' => $data['clock_in'] ?? null,
                'clock_out' => $data['clock_out'] ?? null,
                'break_minutes' => $data['break_minutes'] ?? 0,
                'hours_worked' => $data['hours_worked'] ?? null,
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    public function updateTimesheet(Timesheet $timesheet, array $data): Timesheet
    {
        $timesheet->update($data);
        return $timesheet->fresh();
    }

    public function deleteTimesheet(Timesheet $timesheet): void
    {
        $timesheet->delete();
    }

    public function clockIn(Timesheet $timesheet): Timesheet
    {
        return DB::transaction(function () use ($timesheet) {
            $timesheet->update([
                'clock_in' => now(),
                'status' => 'pending',
            ]);

            return $timesheet->fresh();
        });
    }

    public function clockOut(Timesheet $timesheet): Timesheet
    {
        return DB::transaction(function () use ($timesheet) {
            $timesheet->update([
                'clock_out' => now(),
                'hours_worked' => $this->calculateHoursWorked($timesheet->clock_in, now(), $timesheet->break_minutes),
            ]);

            return $timesheet->fresh();
        });
    }

    public function approveAgency(Timesheet $timesheet): Timesheet
    {
        return DB::transaction(function () use ($timesheet) {
            $timesheet->update([
                'status' => 'agency_approved',
                'agency_approved_by' => auth()->id(),
                'agency_approved_at' => now(),
            ]);

            return $timesheet->fresh();
        });
    }

    public function approveEmployer(Timesheet $timesheet): Timesheet
    {
        return DB::transaction(function () use ($timesheet) {
            $timesheet->update([
                'status' => 'employer_approved',
                'approved_by_contact_id' => auth()->user()->contact->id,
                'approved_at' => now(),
            ]);

            return $timesheet->fresh();
        });
    }

    public function rejectTimesheet(Timesheet $timesheet): Timesheet
    {
        return DB::transaction(function () use ($timesheet) {
            $timesheet->update([
                'status' => 'rejected',
            ]);

            return $timesheet->fresh();
        });
    }

    public function submitTimesheet(Timesheet $timesheet): Timesheet
    {
        return DB::transaction(function () use ($timesheet) {
            if (!$timesheet->clock_out) {
                throw new \Exception('Cannot submit timesheet without clocking out');
            }

            $timesheet->update([
                'status' => 'pending',
            ]);

            return $timesheet->fresh();
        });
    }

    private function calculateHoursWorked($clockIn, $clockOut, $breakMinutes): float
    {
        $diffInMinutes = $clockIn->diffInMinutes($clockOut);
        $workedMinutes = $diffInMinutes - $breakMinutes;
        return round($workedMinutes / 60, 2);
    }
}
