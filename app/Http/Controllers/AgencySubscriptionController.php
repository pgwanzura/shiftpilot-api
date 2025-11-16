<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Agency;
use App\Models\PricePlan;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;

class AgencySubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    public function show(Agency $agency): JsonResponse
    {
        $subscription = $this->subscriptionService->getActiveSubscription($agency);

        return response()->json([
            'has_active_subscription' => $this->subscriptionService->agencyHasActiveSubscription($agency),
            'subscription' => $subscription ? new SubscriptionResource($subscription) : null,
            'features' => $this->getAgencyFeatures($agency),
            'limits' => $this->getAgencyLimits($agency),
        ]);
    }

    public function store(CreateSubscriptionRequest $request, Agency $agency): SubscriptionResource
    {
        $this->authorize('create', [Subscription::class, $agency]);

        $pricePlan = PricePlan::where('plan_key', $request->plan_key)->firstOrFail();
        $subscription = $this->subscriptionService->createSubscription($agency, $pricePlan, $request->interval);

        return new SubscriptionResource($subscription);
    }

    private function getAgencyFeatures(Agency $agency): array
    {
        $features = [
            'advanced_reporting' => $this->subscriptionService->canAgencyAccessFeature($agency, 'advanced_reporting'),
            'api_access' => $this->subscriptionService->canAgencyAccessFeature($agency, 'api_access'),
            'phone_support' => $this->subscriptionService->canAgencyAccessFeature($agency, 'phone_support'),
            'custom_integrations' => $this->subscriptionService->canAgencyAccessFeature($agency, 'custom_integrations'),
        ];

        return array_filter($features);
    }

    private function getAgencyLimits(Agency $agency): array
    {
        return [
            'employees' => $this->subscriptionService->getAgencyLimit($agency, 'employees'),
            'shifts_per_month' => $this->subscriptionService->getAgencyLimit($agency, 'shifts_per_month'),
            'storage_gb' => $this->subscriptionService->getAgencyLimit($agency, 'storage_gb'),
        ];
    }
}
