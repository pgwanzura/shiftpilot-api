<?php

namespace App\Http\Controllers;

use App\Http\Requests\PricePlan\CreatePricePlanRequest;
use App\Http\Requests\PricePlan\UpdatePricePlanRequest;
use App\Http\Resources\PricePlanResource;
use App\Models\PricePlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PricePlanController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $plans = PricePlan::active()->ordered()->get();

        return PricePlanResource::collection($plans);
    }

    public function store(CreatePricePlanRequest $request): PricePlanResource
    {
        $plan = PricePlan::create($request->validated());

        return new PricePlanResource($plan);
    }

    public function show(PricePlan $pricePlan): PricePlanResource
    {
        return new PricePlanResource($pricePlan);
    }

    public function update(UpdatePricePlanRequest $request, PricePlan $pricePlan): PricePlanResource
    {
        $pricePlan->update($request->validated());

        return new PricePlanResource($pricePlan);
    }

    public function destroy(PricePlan $pricePlan): JsonResponse
    {
        if ($pricePlan->subscriptions()->exists()) {
            return response()->json([
                'message' => 'Cannot delete price plan with active subscriptions'
            ], 422);
        }

        $pricePlan->delete();

        return response()->json(['message' => 'Price plan deleted successfully']);
    }

    public function activate(PricePlan $pricePlan): PricePlanResource
    {
        $pricePlan->update(['is_active' => true]);

        return new PricePlanResource($pricePlan);
    }

    public function deactivate(PricePlan $pricePlan): PricePlanResource
    {
        $pricePlan->update(['is_active' => false]);

        return new PricePlanResource($pricePlan);
    }
}
