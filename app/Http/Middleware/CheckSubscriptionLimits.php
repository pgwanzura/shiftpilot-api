<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionLimits
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    public function handle(Request $request, Closure $next, string $limit): Response
    {
        $agency = $request->user()->agency;

        if (!$agency) {
            return response()->json(['message' => 'Agency not found'], 403);
        }

        $currentUsage = $this->getCurrentUsage($request, $limit);

        if (!$this->subscriptionService->isAgencyWithinLimit($agency, $limit, $currentUsage)) {
            return response()->json([
                'message' => 'Subscription limit exceeded',
                'limit' => $limit,
                'current_usage' => $currentUsage,
                'limit_value' => $this->subscriptionService->getAgencyLimit($agency, $limit)
            ], 402);
        }

        return $next($request);
    }

    private function getCurrentUsage(Request $request, string $limit): int
    {
        return match ($limit) {
            'employees' => $request->user()->agency->employees()->count(),
            'shifts_per_month' => $request->user()->agency->shifts()->whereMonth('created_at', now()->month)->count(),
            'storage_gb' => 0, // Implement storage calculation
            default => 0,
        };
    }
}
