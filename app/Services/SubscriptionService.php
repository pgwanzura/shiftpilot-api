<?php

namespace App\Services;

use App\Models\Subscription;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function getSubscriptions(array $filters = []): LengthAwarePaginator
    {
        $query = Subscription::with(['subscriber']);

        if (isset($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (isset($filters['entity_id'])) {
            $query->where('entity_id', $filters['entity_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['plan_key'])) {
            $query->where('plan_key', $filters['plan_key']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('plan_name', 'like', "%{$search}%")
                  ->orWhere('plan_key', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function createSubscription(array $data): Subscription
    {
        return DB::transaction(function () use ($data) {
            return Subscription::create([
                'entity_type' => $data['entity_type'],
                'entity_id' => $data['entity_id'],
                'plan_key' => $data['plan_key'],
                'plan_name' => $data['plan_name'],
                'amount' => $data['amount'],
                'interval' => $data['interval'],
                'status' => 'active',
                'started_at' => $data['started_at'],
                'current_period_start' => $data['started_at'],
                'current_period_end' => $this->calculatePeriodEnd($data['started_at'], $data['interval']),
            ]);
        });
    }

    public function updateSubscription(Subscription $subscription, array $data): Subscription
    {
        $subscription->update($data);
        return $subscription->fresh();
    }

    public function deleteSubscription(Subscription $subscription): void
    {
        $subscription->delete();
    }

    public function cancelSubscription(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            $subscription->update([
                'status' => 'cancelled',
            ]);

            return $subscription->fresh();
        });
    }

    public function renewSubscription(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            $currentPeriodEnd = $subscription->current_period_end ?? now();
            $newPeriodStart = $currentPeriodEnd;
            $newPeriodEnd = $this->calculatePeriodEnd($newPeriodStart, $subscription->interval);

            $subscription->update([
                'status' => 'active',
                'current_period_start' => $newPeriodStart,
                'current_period_end' => $newPeriodEnd,
            ]);

            return $subscription->fresh();
        });
    }

    public function changePlan(Subscription $subscription, array $data): Subscription
    {
        return DB::transaction(function () use ($subscription, $data) {
            $subscription->update([
                'plan_key' => $data['plan_key'],
                'plan_name' => $data['plan_name'],
                'amount' => $data['amount'],
            ]);

            return $subscription->fresh();
        });
    }

    private function calculatePeriodEnd($startDate, $interval): string
    {
        $start = \Carbon\Carbon::parse($startDate);

        return match($interval) {
            'monthly' => $start->addMonth(),
            'yearly' => $start->addYear(),
            default => $start->addMonth(),
        };
    }
}
