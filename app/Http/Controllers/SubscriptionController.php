<?php

namespace App\Http\Controllers;

use App\Http\Requests\Subscription\CreateSubscriptionRequest;
use App\Http\Requests\Subscription\UpdateSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $subscriptions = $this->subscriptionService->getSubscriptions($request->all());
        return response()->json([
            'success' => true,
            'data' => $subscriptions,
            'message' => 'Subscriptions retrieved successfully'
        ]);
    }

    public function store(CreateSubscriptionRequest $request): JsonResponse
    {
        $subscription = $this->subscriptionService->createSubscription($request->validated());
        return response()->json([
            'success' => true,
            'data' => new SubscriptionResource($subscription),
            'message' => 'Subscription created successfully'
        ]);
    }

    public function show(Subscription $subscription): JsonResponse
    {
        $subscription->load(['subscriber']);
        return response()->json([
            'success' => true,
            'data' => new SubscriptionResource($subscription),
            'message' => 'Subscription retrieved successfully'
        ]);
    }

    public function update(UpdateSubscriptionRequest $request, Subscription $subscription): JsonResponse
    {
        $subscription = $this->subscriptionService->updateSubscription($subscription, $request->validated());
        return response()->json([
            'success' => true,
            'data' => new SubscriptionResource($subscription),
            'message' => 'Subscription updated successfully'
        ]);
    }

    public function destroy(Subscription $subscription): JsonResponse
    {
        $this->subscriptionService->deleteSubscription($subscription);
        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Subscription deleted successfully'
        ]);
    }

    public function cancel(Subscription $subscription): JsonResponse
    {
        $this->authorize('update', $subscription);
        $subscription = $this->subscriptionService->cancelSubscription($subscription);

        return response()->json([
            'success' => true,
            'data' => new SubscriptionResource($subscription),
            'message' => 'Subscription cancelled successfully'
        ]);
    }

    public function renew(Subscription $subscription): JsonResponse
    {
        $this->authorize('update', $subscription);
        $subscription = $this->subscriptionService->renewSubscription($subscription);

        return response()->json([
            'success' => true,
            'data' => new SubscriptionResource($subscription),
            'message' => 'Subscription renewed successfully'
        ]);
    }

    public function changePlan(Subscription $subscription, Request $request): JsonResponse
    {
        $this->authorize('update', $subscription);
        $request->validate([
            'plan_key' => 'required|string',
            'plan_name' => 'required|string',
            'amount' => 'required|numeric|min:0'
        ]);

        $subscription = $this->subscriptionService->changePlan($subscription, $request->all());

        return response()->json([
            'success' => true,
            'data' => new SubscriptionResource($subscription),
            'message' => 'Subscription plan changed successfully'
        ]);
    }
}
