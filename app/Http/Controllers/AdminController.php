<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Http\Resources\DashboardStatsResource;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\PayoutResource;
use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\UserResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(
        private AdminService $adminService
    ) {
    }

    /**
     * Get admin dashboard statistics
     */
    public function dashboardStats(): JsonResponse
    {
        $stats = $this->adminService->getDashboardStats();

        return response()->json([
            'success' => true,
            'data' => new DashboardStatsResource($stats),
            'message' => 'Dashboard stats retrieved successfully'
        ]);
    }

    /**
     * Get all invoices with pagination
     */
    public function invoices(Request $request): JsonResponse
    {
        $invoices = Invoice::with(['from', 'to'])
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $invoices, // Direct paginated response
            'message' => 'Invoices retrieved successfully'
        ]);
    }

    /**
     * Get all payments with pagination
     */
    public function payments(Request $request): JsonResponse
    {
        $payments = Payment::with(['invoice'])
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $payments, // Direct paginated response
            'message' => 'Payments retrieved successfully'
        ]);
    }

    /**
     * Get all payouts with pagination
     */
    public function payouts(Request $request): JsonResponse
    {
        $payouts = Payout::with(['agency', 'payrolls'])
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $payouts, // Direct paginated response
            'message' => 'Payouts retrieved successfully'
        ]);
    }

    /**
     * Get all subscriptions with pagination
     */
    public function subscriptions(Request $request): JsonResponse
    {
        $subscriptions = Subscription::with(['subscriber'])
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $subscriptions, // Direct paginated response
            'message' => 'Subscriptions retrieved successfully'
        ]);
    }

    /**
     * Get all users with pagination and filters
     */
    public function users(Request $request): JsonResponse
    {
        $query = User::with(['profile']);

        // Apply filters
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $users, // Direct paginated response
            'message' => 'Users retrieved successfully'
        ]);
    }

    /**
     * Update user status
     */
    public function updateUserStatus(User $user, UpdateUserStatusRequest $request): JsonResponse
    {
        $user->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'User status updated successfully'
        ]);
    }
}
