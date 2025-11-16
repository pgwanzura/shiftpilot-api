<?php

namespace App\Http\Controllers;

use App\Http\Resources\SubscriptionResource;
use App\Models\Agency;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    public function index(Request $request)
    {
        $subscriptions = $this->subscriptionService->getSubscriptions($request->all());
        return SubscriptionResource::collection($subscriptions);
    }

    public function show(Subscription $subscription): SubscriptionResource
    {
        return new SubscriptionResource($subscription->load(['agency', 'pricePlan']));
    }

    public function cancel(Subscription $subscription): SubscriptionResource
    {
        $this->authorize('cancel', $subscription);
        $updatedSubscription = $this->subscriptionService->cancelSubscription($subscription);
        return new SubscriptionResource($updatedSubscription);
    }

    public function renew(Subscription $subscription): SubscriptionResource
    {
        $this->authorize('renew', $subscription);
        $updatedSubscription = $this->subscriptionService->renewSubscription($subscription);
        return new SubscriptionResource($updatedSubscription);
    }
}
