<?php

// app/Services/PlatformInvoiceService.php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Timesheet;
use App\Models\Shift;
use App\Models\Agency;
use App\Events\InvoiceGenerated;
use Carbon\Carbon;

class PlatformInvoiceService
{
    public function generatePlatformFeeInvoice(Timesheet $timesheet): Invoice
    {
        $shift = $timesheet->shift;
        $platformFee = $this->calculatePlatformFee($shift, $timesheet->hours_worked);

        $invoice = Invoice::create([
            'type' => 'employer_to_shiftpilot',
            'from_type' => 'employer',
            'from_id' => $shift->employer_id,
            'to_type' => 'shiftpilot',
            'to_id' => 1, // ShiftPilot platform entity
            'reference' => $this->generateInvoiceReference(),
            'line_items' => [
                [
                    'description' => __('invoices.platform_fee_shift', [
                        'role' => $shift->role_requirement,
                        'date' => $shift->start_time->format('Y-m-d')
                    ]),
                    'quantity' => $timesheet->hours_worked,
                    'unit_price' => $platformFee / $timesheet->hours_worked,
                    'total' => $platformFee,
                ]
            ],
            'subtotal' => $platformFee,
            'tax_amount' => 0, // Could be calculated based on country
            'total_amount' => $platformFee,
            'status' => 'pending',
            'due_date' => Carbon::now()->addDays(14),
            'timesheet_id' => $timesheet->id,
        ]);

        event(new InvoiceGenerated($invoice));

        return $invoice;
    }

    public function generateAgencyCommissionInvoice(Timesheet $timesheet): Invoice
    {
        $shift = $timesheet->shift;
        $agency = $shift->agency;
        $commissionAmount = $this->calculateAgencyCommission($shift, $timesheet->hours_worked);

        $invoice = Invoice::create([
            'type' => 'employer_to_agency',
            'from_type' => 'employer',
            'from_id' => $shift->employer_id,
            'to_type' => 'agency',
            'to_id' => $agency->id,
            'reference' => $this->generateInvoiceReference(),
            'line_items' => [
                [
                    'description' => __('invoices.agency_commission_shift', [
                        'role' => $shift->role_requirement,
                        'employee' => $timesheet->employee->name,
                        'date' => $shift->start_time->format('Y-m-d')
                    ]),
                    'quantity' => $timesheet->hours_worked,
                    'unit_price' => $shift->hourly_rate,
                    'total' => $timesheet->hours_worked * $shift->hourly_rate,
                ],
                [
                    'description' => __('invoices.platform_commission'),
                    'quantity' => 1,
                    'unit_price' => -$commissionAmount, // Negative amount as deduction
                    'total' => -$commissionAmount,
                ]
            ],
            'subtotal' => ($timesheet->hours_worked * $shift->hourly_rate) - $commissionAmount,
            'tax_amount' => $this->calculateTax(($timesheet->hours_worked * $shift->hourly_rate) - $commissionAmount),
            'total_amount' => ($timesheet->hours_worked * $shift->hourly_rate) - $commissionAmount + $this->calculateTax(($timesheet->hours_worked * $shift->hourly_rate) - $commissionAmount),
            'status' => 'pending',
            'due_date' => Carbon::now()->addDays(30),
            'timesheet_id' => $timesheet->id,
        ]);

        event(new InvoiceGenerated($invoice));

        return $invoice;
    }

    public function generateCompleteBilling(Timesheet $timesheet): array
    {
        $platformInvoice = $this->generatePlatformFeeInvoice($timesheet);
        $agencyInvoice = $this->generateAgencyCommissionInvoice($timesheet);

        return [
            'platform_invoice' => $platformInvoice,
            'agency_invoice' => $agencyInvoice,
        ];
    }

    public function markInvoiceAsPaid(Invoice $invoice): Invoice
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => Carbon::now(),
        ]);

        return $invoice->fresh();
    }

    public function markInvoiceAsOverdue(Invoice $invoice): Invoice
    {
        if ($invoice->due_date->isPast() && $invoice->status === 'pending') {
            $invoice->update([
                'status' => 'overdue',
            ]);
        }

        return $invoice->fresh();
    }

    private function calculatePlatformFee(Shift $shift, float $hoursWorked): float
    {
        $baseRate = $shift->hourly_rate ?? 25.00;
        $commissionRate = config('shiftpilot.platform_billing.commission_rate', 0.02); // 2%

        return $baseRate * $hoursWorked * $commissionRate;
    }

    private function calculateAgencyCommission(Shift $shift, float $hoursWorked): float
    {
        $baseRate = $shift->hourly_rate ?? 25.00;
        $agencyCommissionRate = config('shiftpilot.platform_billing.agency_commission_rate', 0.15); // 15%

        return $baseRate * $hoursWorked * $agencyCommissionRate;
    }

    private function calculateTax(float $amount): float
    {
        $taxRate = config('shiftpilot.platform_billing.tax_rate', 0.00);

        return $amount * $taxRate;
    }

    private function generateInvoiceReference(): string
    {
        return 'INV-' . date('Ymd') . '-' . str_pad(Invoice::count() + 1, 4, '0', STR_PAD_LEFT);
    }

    public function getOutstandingInvoices($entityType, $entityId): \Illuminate\Database\Eloquent\Collection
    {
        return Invoice::where('from_type', $entityType)
            ->where('from_id', $entityId)
            ->whereIn('status', ['pending', 'overdue'])
            ->get();
    }

    public function getInvoiceSummary($entityType, $entityId): array
    {
        $invoices = $this->getOutstandingInvoices($entityType, $entityId);

        return [
            'total_outstanding' => $invoices->sum('total_amount'),
            'pending_count' => $invoices->where('status', 'pending')->count(),
            'overdue_count' => $invoices->where('status', 'overdue')->count(),
            'invoices' => $invoices,
        ];
    }
}
