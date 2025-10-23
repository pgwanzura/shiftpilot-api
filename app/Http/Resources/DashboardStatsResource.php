<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStatsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'total_users' => $this['total_users'] ?? null,
            'total_agencies' => $this['total_agencies'] ?? null,
            'total_employers' => $this['total_employers'] ?? null,
            'total_employees' => $this['total_employees'] ?? null,
            'total_shifts' => $this['total_shifts'] ?? null,
            'active_shifts' => $this['active_shifts'] ?? null,
            'completed_shifts' => $this['completed_shifts'] ?? null,
            'pending_invoices' => $this['pending_invoices'] ?? null,
            'total_revenue' => $this['total_revenue'] ?? null,
            'platform_commission' => $this['platform_commission'] ?? null,
            'total_placements' => $this['total_placements'] ?? null,
            'active_placements' => $this['active_placements'] ?? null,
            'pending_timesheets' => $this['pending_timesheets'] ?? null,
            'upcoming_payouts' => $this['upcoming_payouts'] ?? null,
            'total_locations' => $this['total_locations'] ?? null,
            'total_contacts' => $this['total_contacts'] ?? null,
            'open_shifts' => $this['open_shifts'] ?? null,
            'assigned_shifts' => $this['assigned_shifts'] ?? null,
            'pending_approvals' => $this['pending_approvals'] ?? null,
            'total_spent' => $this['total_spent'] ?? null,
            'approved_this_week' => $this['approved_this_week'] ?? null,
            'total_approvals' => $this['total_approvals'] ?? null,
            'upcoming_shifts' => $this['upcoming_shifts'] ?? null,
            'completed_shifts_count' => $this['completed_shifts_count'] ?? null,
            'total_earnings' => $this['total_earnings'] ?? null,
            'pending_payments' => $this['pending_payments'] ?? null,
            'pending_shift_offers' => $this['pending_shift_offers'] ?? null,
            'pending_offers' => $this['pending_offers'] ?? null,
            'accepted_offers' => $this['accepted_offers'] ?? null,
        ];
    }
}
