<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\Employer;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Shift;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class AdminService
{
    public function getDashboardStats(): array
    {
        return [
            'total_users' => User::count(),
            'total_agencies' => Agency::count(),
            'total_employers' => Employer::count(),
            'total_employees' => Employee::count(),
            'total_shifts' => Shift::count(),
            'active_shifts' => Shift::whereIn('status', ['open', 'offered', 'assigned'])->count(),
            'completed_shifts' => Shift::where('status', 'completed')->count(),
            'pending_invoices' => Invoice::where('status', 'pending')->count(),
            'total_revenue' => Invoice::where('status', 'paid')->sum('total_amount'),
            'platform_commission' => $this->calculatePlatformCommission(),
            'recent_activity' => $this->getRecentActivity(),
        ];
    }

    private function calculatePlatformCommission(): float
    {
        return Invoice::where('status', 'paid')
            ->where('type', 'agency_to_shiftpilot')
            ->sum('total_amount');
    }

    private function getRecentActivity(): array
    {
        return AuditLog::with(['actor'])
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'actor_name' => $log->actor?->name ?? 'System',
                    'target_type' => $log->target_type,
                    'created_at' => $log->created_at->toISOString(),
                ];
            })
            ->toArray();
    }
}
