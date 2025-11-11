<?php
// app/Listeners/HandleAssignmentCompletion.php

namespace App\Listeners\Assignment;

use App\Events\AssignmentCompleted;
use App\Services\InvoiceService;
use App\Services\PayrollService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleAssignmentCompletion implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private InvoiceService $invoiceService,
        private PayrollService $payrollService
    ) {}

    public function handle(AssignmentCompleted $event): void
    {
        $assignment = $event->assignment;

        // Generate final invoice for the assignment
        $this->invoiceService->generateFinalInvoice($assignment);

        // Process final payroll for the employee
        $this->payrollService->processFinalPayroll($assignment);

        // Log assignment completion for analytics
        \App\Models\AuditLog::create([
            'action' => 'assignment_completed',
            'description' => "Assignment {$assignment->id} completed for {$assignment->agencyEmployee->employee->user->name}",
            'user_id' => auth()->id() ?? null,
            'target_type' => Assignment::class,
            'target_id' => $assignment->id,
        ]);
    }
}
