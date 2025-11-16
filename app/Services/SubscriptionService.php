<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\PricePlan;
use App\Models\Subscription;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function getSubscriptions(array $filters = []): LengthAwarePaginator
    {
        $query = Subscription::with(['agency', 'pricePlan']);

        if (isset($filters['agency_id'])) {
            $query->where('agency_id', $filters['agency_id']);
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
                    ->orWhere('plan_key', 'like', "%{$search}%")
                    ->orWhereHas('agency', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function createSubscription(Agency $agency, PricePlan $pricePlan, string $interval = 'monthly'): Subscription
    {
        return DB::transaction(function () use ($agency, $pricePlan, $interval) {
            $amount = $interval === 'yearly' ? $pricePlan->getYearlyAmount() : $pricePlan->getMonthlyAmount();
            $periodEnd = $interval === 'yearly' ? now()->addYear() : now()->addMonth();

            return Subscription::create([
                'agency_id' => $agency->id,
                'plan_key' => $pricePlan->plan_key,
                'plan_name' => $pricePlan->name,
                'amount' => $amount,
                'interval' => $interval,
                'status' => 'active',
                'started_at' => now(),
                'current_period_start' => now(),
                'current_period_end' => $periodEnd,
            ]);
        });
    }

    public function updateSubscription(Subscription $subscription, array $data): Subscription
    {
        $subscription->update($data);
        return $subscription->fresh(['agency', 'pricePlan']);
    }

    public function deleteSubscription(Subscription $subscription): void
    {
        $subscription->delete();
    }

    public function cancelSubscription(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            $subscription->update(['status' => 'cancelled']);
            return $subscription->fresh(['agency', 'pricePlan']);
        });
    }

    public function renewSubscription(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            $currentPeriodEnd = $subscription->current_period_end ?? now();
            $newPeriodEnd = $this->calculatePeriodEnd($currentPeriodEnd, $subscription->interval);

            $subscription->update([
                'status' => 'active',
                'current_period_start' => $currentPeriodEnd,
                'current_period_end' => $newPeriodEnd,
            ]);

            return $subscription->fresh(['agency', 'pricePlan']);
        });
    }

    public function changePlan(Subscription $subscription, PricePlan $newPricePlan, string $interval = 'monthly'): Subscription
    {
        return DB::transaction(function () use ($subscription, $newPricePlan, $interval) {
            $amount = $interval === 'yearly' ? $newPricePlan->getYearlyAmount() : $newPricePlan->getMonthlyAmount();

            $subscription->update([
                'plan_key' => $newPricePlan->plan_key,
                'plan_name' => $newPricePlan->name,
                'amount' => $amount,
                'interval' => $interval,
            ]);

            return $subscription->fresh(['agency', 'pricePlan']);
        });
    }

    public function getActiveSubscription(Agency $agency): ?Subscription
    {
        return Subscription::forAgency($agency->id)
            ->active()
            ->where('current_period_end', '>', now())
            ->with('pricePlan')
            ->first();
    }

    public function agencyHasActiveSubscription(Agency $agency): bool
    {
        return $this->getActiveSubscription($agency) !== null;
    }

    public function canAgencyAccessFeature(Agency $agency, string $feature): bool
    {
        $subscription = $this->getActiveSubscription($agency);
        return $subscription && $subscription->pricePlan && $subscription->pricePlan->hasFeature($feature);
    }

    public function getAgencyLimit(Agency $agency, string $limit): ?int
    {
        $subscription = $this->getActiveSubscription($agency);
        return $subscription && $subscription->pricePlan ? $subscription->pricePlan->getLimit($limit) : null;
    }

    public function isAgencyWithinLimit(Agency $agency, string $limit, int $currentUsage): bool
    {
        $limitValue = $this->getAgencyLimit($agency, $limit);
        return $limitValue === null || $currentUsage < $limitValue;
    }

    private function calculatePeriodEnd($startDate, string $interval): \Carbon\Carbon
    {
        $start = \Carbon\Carbon::parse($startDate);

        return match ($interval) {
            'monthly' => $start->addMonth(),
            'yearly' => $start->addYear(),
            default => $start->addMonth(),
        };
    }
}
