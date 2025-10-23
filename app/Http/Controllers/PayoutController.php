<?php

namespace App\Http\Controllers;

use App\Http\Resources\PayoutResource;
use App\Models\Payout;
use App\Services\PayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    public function __construct(
        private PayoutService $payoutService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $payouts = $this->payoutService->getPayouts($request->all());
        return response()->json([
            'success' => true,
            'data' => $payouts,
            'message' => 'Payouts retrieved successfully'
        ]);
    }

    public function show(Payout $payout): JsonResponse
    {
        $payout->load(['agency', 'payrolls.employee.user']);
        return response()->json([
            'success' => true,
            'data' => new PayoutResource($payout),
            'message' => 'Payout retrieved successfully'
        ]);
    }

    public function process(Request $request): JsonResponse
    {
        $this->authorize('create', Payout::class);
        $request->validate([
            'agency_id' => 'required|exists:agencies,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start'
        ]);

        $payout = $this->payoutService->processPayout($request->all());

        return response()->json([
            'success' => true,
            'data' => new PayoutResource($payout),
            'message' => 'Payout processed successfully'
        ]);
    }

    public function markAsPaid(Payout $payout): JsonResponse
    {
        $this->authorize('update', $payout);
        $payout = $this->payoutService->markAsPaid($payout);

        return response()->json([
            'success' => true,
            'data' => new PayoutResource($payout),
            'message' => 'Payout marked as paid successfully'
        ]);
    }

    public function retry(Payout $payout): JsonResponse
    {
        $this->authorize('update', $payout);
        $payout = $this->payoutService->retryPayout($payout);

        return response()->json([
            'success' => true,
            'data' => new PayoutResource($payout),
            'message' => 'Payout retry initiated successfully'
        ]);
    }
}
