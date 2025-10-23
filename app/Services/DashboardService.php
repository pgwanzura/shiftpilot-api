<?php

namespace App\Services;

use App\Models\User;
use App\Models\Agency;
use App\Models\Employer;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\Timesheet;
use App\Models\Invoice;
use App\Models\Payroll;
use App\Models\ShiftOffer;
use App\Models\ShiftApproval;

class DashboardService
{
    public function getDashboardStats(User $user): array
    {
        return match($user->role) {
            'super_admin' => $this->getSuperAdminStats(),
            'agency_admin' => $this->getAgencyAdminStats($user->agency),
            'agent' => $this->getAgentStats($user->agency),
            'employer_admin' => $this->getEmployerAdminStats($user->employer),
            'contact' => $this->getContactStats($user),
            'employee' => $this->getEmployeeStats($user->employee),
            default => []
        };
    }

    private function getSuperAdminStats(): array
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
            'platform_commission' => Invoice::where('status', 'paid')->where('type', 'agency_to_shiftpilot')->sum('total_amount'),
        ];
    }

    private function getAgencyAdminStats(Agency $agency): array
    {
        return [
            'total_employees' => $agency->employees()->count(),
            'active_placements' => $agency->placements()->where('status', 'active')->count(),
            'pending_timesheets' => $agency->timesheets()->where('status', 'pending')->count(),
            'total_shifts' => $agency->shifts()->count(),
            'active_shifts' => $agency->shifts()->whereIn('status', ['open', 'offered', 'assigned'])->count(),
            'completed_shifts' => $agency->shifts()->where('status', 'completed')->count(),
            'pending_invoices' => $agency->invoices()->where('status', 'pending')->count(),
            'total_revenue' => $agency->invoices()->where('status', 'paid')->sum('total_amount'),
            'upcoming_payouts' => $agency->payouts()->where('status', 'processing')->sum('total_amount'),
        ];
    }

    private function getAgentStats(Agency $agency): array
    {
        return [
            'assigned_shifts' => $agency->shifts()->where('agent_id', auth()->id())->count(),
            'pending_offers' => ShiftOffer::where('offered_by_id', auth()->id())->where('status', 'pending')->count(),
            'accepted_offers' => ShiftOffer::where('offered_by_id', auth()->id())->where('status', 'accepted')->count(),
            'total_employees' => $agency->employees()->count(),
            'active_placements' => $agency->placements()->where('status', 'active')->count(),
        ];
    }

    private function getEmployerAdminStats(Employer $employer): array
    {
        return [
            'total_locations' => $employer->locations()->count(),
            'total_contacts' => $employer->contacts()->count(),
            'open_shifts' => $employer->shifts()->where('status', 'open')->count(),
            'assigned_shifts' => $employer->shifts()->where('status', 'assigned')->count(),
            'completed_shifts' => $employer->shifts()->where('status', 'completed')->count(),
            'pending_approvals' => ShiftApproval::whereHas('shift', function ($query) use ($employer) {
                $query->where('employer_id', $employer->id);
            })->where('status', 'pending')->count(),
            'pending_invoices' => $employer->invoices()->where('status', 'pending')->count(),
            'total_spent' => $employer->invoices()->where('status', 'paid')->sum('total_amount'),
        ];
    }

    private function getContactStats(User $user): array
    {
        $contact = $user->contact;

        return [
            'pending_approvals' => ShiftApproval::where('contact_id', $contact->id)->where('status', 'pending')->count(),
            'approved_this_week' => ShiftApproval::where('contact_id', $contact->id)->where('status', 'approved')
                ->where('created_at', '>=', now()->subWeek())->count(),
            'total_approvals' => ShiftApproval::where('contact_id', $contact->id)->count(),
        ];
    }

    private function getEmployeeStats(Employee $employee): array
    {
        return [
            'upcoming_shifts' => $employee->shifts()->whereIn('status', ['assigned'])->count(),
            'completed_shifts' => $employee->shifts()->where('status', 'completed')->count(),
            'total_earnings' => $employee->payroll()->where('status', 'paid')->sum('net_pay'),
            'pending_payments' => $employee->payroll()->where('status', 'unpaid')->sum('net_pay'),
            'pending_shift_offers' => $employee->shiftOffers()->where('status', 'pending')->count(),
        ];
    }
}
