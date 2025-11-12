<?php

namespace App\Http\Controllers;

use App\Models\RateCard;
use App\Http\Requests\RateCard\StoreRateCardRequest;
use App\Http\Requests\RateCard\UpdateRateCardRequest;
use App\Services\RateCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RateCardController extends Controller
{
    protected $rateCardService;

    public function __construct(RateCardService $rateCardService)
    {
        $this->rateCardService = $rateCardService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $rateCards = $this->rateCardService->getAllRateCards();
        return response()->json($rateCards);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRateCardRequest $request): JsonResponse
    {
        $rateCard = $this->rateCardService->createRateCard($request->validated());
        return response()->json($rateCard, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(RateCard $rateCard): JsonResponse
    {
        return response()->json($rateCard);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRateCardRequest $request, RateCard $rateCard): JsonResponse
    {
        $rateCard = $this->rateCardService->updateRateCard($rateCard, $request->validated());
        return response()->json($rateCard);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RateCard $rateCard): JsonResponse
    {
        $this->rateCardService->deleteRateCard($rateCard);
        return response()->json(null, 204);
    }
}
