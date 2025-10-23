<?php

namespace App\Http\Controllers;

use App\Http\Resources\DashboardStatsResource;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {
    }

    public function stats(): JsonResponse
    {
        $user = auth()->user();
        $stats = $this->dashboardService->getDashboardStats($user);

        return response()->json([
            'success' => true,
            'data' => new DashboardStatsResource($stats),
            'message' => 'Dashboard stats retrieved successfully'
        ]);
    }
}
