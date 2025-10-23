<?php

namespace App\Services;

use App\Models\ShiftApproval;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ShiftApprovalService
{
    public function getShiftApprovals(array $filters = []): LengthAwarePaginator
    {
        $query = ShiftApproval::with(['shift', 'contact.user']);

        if (isset($filters['shift_id'])) {
            $query->where('shift_id', $filters['shift_id']);
        }

        if (isset($filters['contact_id'])) {
            $query->where('contact_id', $filters['contact_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['employer_id'])) {
            $query->whereHas('shift', function ($q) use ($filters) {
                $q->where('employer_id', $filters['employer_id']);
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function createShiftApproval(array $data): ShiftApproval
    {
        return DB::transaction(function () use ($data) {
            return ShiftApproval::create([
                'shift_id' => $data['shift_id'],
                'contact_id' => $data['contact_id'],
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    public function updateShiftApproval(ShiftApproval $approval, array $data): ShiftApproval
    {
        $approval->update($data);
        return $approval->fresh();
    }

    public function deleteShiftApproval(ShiftApproval $approval): void
    {
        $approval->delete();
    }

    public function approveShiftApproval(ShiftApproval $approval): ShiftApproval
    {
        return DB::transaction(function () use ($approval) {
            $approval->update([
                'status' => 'approved',
                'signed_at' => now(),
            ]);

            $shift = $approval->shift;
            $shift->update(['status' => 'employer_approved']);

            return $approval->fresh();
        });
    }

    public function rejectShiftApproval(ShiftApproval $approval): ShiftApproval
    {
        return DB::transaction(function () use ($approval) {
            $approval->update([
                'status' => 'rejected',
                'signed_at' => now(),
            ]);

            $shift = $approval->shift;
            $shift->update(['status' => 'cancelled']);

            return $approval->fresh();
        });
    }
}
