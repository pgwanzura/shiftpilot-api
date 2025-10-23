<?php

namespace App\Services;

use App\Models\ShiftOffer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ShiftOfferService
{
    public function getShiftOffers(array $filters = []): LengthAwarePaginator
    {
        $query = ShiftOffer::with(['shift', 'employee.user', 'offeredBy']);

        if (isset($filters['shift_id'])) {
            $query->where('shift_id', $filters['shift_id']);
        }

        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['offered_by_id'])) {
            $query->where('offered_by_id', $filters['offered_by_id']);
        }

        if (isset($filters['expired']) && $filters['expired'] === 'true') {
            $query->where('expires_at', '<', now());
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function createShiftOffer(array $data): ShiftOffer
    {
        return DB::transaction(function () use ($data) {
            return ShiftOffer::create([
                'shift_id' => $data['shift_id'],
                'employee_id' => $data['employee_id'],
                'offered_by_id' => auth()->id(),
                'status' => 'pending',
                'expires_at' => $data['expires_at'],
            ]);
        });
    }

    public function updateShiftOffer(ShiftOffer $offer, array $data): ShiftOffer
    {
        $offer->update($data);
        return $offer->fresh();
    }

    public function deleteShiftOffer(ShiftOffer $offer): void
    {
        $offer->delete();
    }

    public function acceptShiftOffer(ShiftOffer $offer): ShiftOffer
    {
        return DB::transaction(function () use ($offer) {
            $offer->update([
                'status' => 'accepted',
                'responded_at' => now(),
            ]);

            $offer->shift->update([
                'employee_id' => $offer->employee_id,
                'status' => 'assigned',
            ]);

            ShiftOffer::where('shift_id', $offer->shift_id)
                ->where('id', '!=', $offer->id)
                ->where('status', 'pending')
                ->update(['status' => 'expired']);

            return $offer->fresh();
        });
    }

    public function rejectShiftOffer(ShiftOffer $offer): ShiftOffer
    {
        return DB::transaction(function () use ($offer) {
            $offer->update([
                'status' => 'rejected',
                'responded_at' => now(),
            ]);

            return $offer->fresh();
        });
    }

    public function expireShiftOffer(ShiftOffer $offer): ShiftOffer
    {
        return DB::transaction(function () use ($offer) {
            $offer->update([
                'status' => 'expired',
            ]);

            return $offer->fresh();
        });
    }
}
