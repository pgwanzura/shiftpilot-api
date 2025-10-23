<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShiftOffer\CreateShiftOfferRequest;
use App\Http\Requests\ShiftOffer\UpdateShiftOfferRequest;
use App\Http\Resources\ShiftOfferResource;
use App\Models\ShiftOffer;
use App\Services\ShiftOfferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftOfferController extends Controller
{
    public function __construct(
        private ShiftOfferService $shiftOfferService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $offers = $this->shiftOfferService->getShiftOffers($request->all());
        return response()->json([
            'success' => true,
            'data' => $offers,
            'message' => 'Shift offers retrieved successfully'
        ]);
    }

    public function store(CreateShiftOfferRequest $request): JsonResponse
    {
        $offer = $this->shiftOfferService->createShiftOffer($request->validated());
        return response()->json([
            'success' => true,
            'data' => new ShiftOfferResource($offer),
            'message' => 'Shift offer created successfully'
        ]);
    }

    public function show(ShiftOffer $offer): JsonResponse
    {
        $offer->load(['shift', 'employee.user', 'offeredBy']);
        return response()->json([
            'success' => true,
            'data' => new ShiftOfferResource($offer),
            'message' => 'Shift offer retrieved successfully'
        ]);
    }

    public function update(UpdateShiftOfferRequest $request, ShiftOffer $offer): JsonResponse
    {
        $offer = $this->shiftOfferService->updateShiftOffer($offer, $request->validated());
        return response()->json([
            'success' => true,
            'data' => new ShiftOfferResource($offer),
            'message' => 'Shift offer updated successfully'
        ]);
    }

    public function destroy(ShiftOffer $offer): JsonResponse
    {
        $this->shiftOfferService->deleteShiftOffer($offer);
        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Shift offer deleted successfully'
        ]);
    }

    public function accept(ShiftOffer $offer): JsonResponse
    {
        $this->authorize('respond', $offer);
        $offer = $this->shiftOfferService->acceptShiftOffer($offer);

        return response()->json([
            'success' => true,
            'data' => new ShiftOfferResource($offer),
            'message' => 'Shift offer accepted successfully'
        ]);
    }

    public function reject(ShiftOffer $offer): JsonResponse
    {
        $this->authorize('respond', $offer);
        $offer = $this->shiftOfferService->rejectShiftOffer($offer);

        return response()->json([
            'success' => true,
            'data' => new ShiftOfferResource($offer),
            'message' => 'Shift offer rejected successfully'
        ]);
    }

    public function expire(ShiftOffer $offer): JsonResponse
    {
        $this->authorize('update', $offer);
        $offer = $this->shiftOfferService->expireShiftOffer($offer);

        return response()->json([
            'success' => true,
            'data' => new ShiftOfferResource($offer),
            'message' => 'Shift offer expired successfully'
        ]);
    }
}
