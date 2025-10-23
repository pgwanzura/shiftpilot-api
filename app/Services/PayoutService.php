<?php

namespace App\Services;

use App\Models\Payout;
use App\Models\Payroll;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PayoutService
{
    public function getPayouts(array $filters = []): LengthAwarePaginator
    {
        $query = Payout::with(['agency']);

        if (isset($filters['agency_id'])) {
            $query->where('agency_id', $filters['agency_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['period_start'])) {
            $query->where('period_start', '>=', $filters['period_start']);
        }

        if (isset($filters['period_end'])) {
            $query->where('period_end', '<=', $filters['period_end']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function processPayout(array $data): Payout
    {
        return DB::transaction(function () use ($data) {
            $unpaidPayroll = Payroll::where('agency_id', $data['agency_id'])
                ->where('status', 'unpaid')
                ->whereBetween('period_start', [$data['period_start'], $data['period_end']])
                ->get();

            if ($unpaidPayroll->isEmpty()) {
                throw new \Exception('No unpaid payroll found for the specified period');
            }

            $totalAmount = $unpaidPayroll->sum('net_pay');

            $payout = Payout::create([
                'agency_id' => $data['agency_id'],
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'total_amount' => $totalAmount,
                'status' => 'processing',
            ]);

            Payroll::whereIn('id', $unpaidPayroll->pluck('id'))
                ->update(['payout_id' => $payout->id]);

            $this->processPayoutWithProvider($payout);

            return $payout->fresh();
        });
    }

    public function markAsPaid(Payout $payout): Payout
    {
        return DB::transaction(function () use ($payout) {
            $payout->update([
                'status' => 'paid',
                'provider_payout_id' => 'MANUAL_' . time(),
            ]);

            Payroll::where('payout_id', $payout->id)
                ->update(['status' => 'paid', 'paid_at' => now()]);

            return $payout->fresh();
        });
    }

    public function retryPayout(Payout $payout): Payout
    {
        if ($payout->status !== 'failed') {
            throw new \Exception('Only failed payouts can be retried');
        }

        return DB::transaction(function () use ($payout) {
            $payout->update([
                'status' => 'processing',
            ]);

            $this->processPayoutWithProvider($payout);

            return $payout->fresh();
        });
    }

    private function processPayoutWithProvider(Payout $payout): void
    {
        try {
            $providerPayoutId = 'STRIPE_PAYOUT_' . uniqid();

            $payout->update([
                'status' => 'paid',
                'provider_payout_id' => $providerPayoutId,
            ]);

            Payroll::where('payout_id', $payout->id)
                ->update(['status' => 'paid', 'paid_at' => now()]);

        } catch (\Exception $e) {
            $payout->update([
                'status' => 'failed',
                'metadata' => array_merge($payout->metadata ?? [], [
                    'error' => $e->getMessage(),
                    'retry_count' => ($payout->metadata['retry_count'] ?? 0) + 1
                ])
            ]);

            throw $e;
        }
    }
}
