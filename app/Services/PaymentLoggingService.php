<?php

// app/Services/PaymentLoggingService.php

namespace App\Services;

use App\Contracts\Services\PaymentLoggingServiceInterface;
use App\Models\PaymentLog;
use App\Models\Invoice;
use App\Models\User;
use App\Events\PaymentLogged;
use App\Events\PaymentConfirmed;
use Illuminate\Database\Eloquent\Collection;

class PaymentLoggingService implements PaymentLoggingServiceInterface
{
    public function logPlatformPayment(Invoice $invoice, array $paymentData): PaymentLog
    {
        // Validate required payment data
        if (!isset($paymentData['amount_paid']) || !isset($paymentData['payment_method']) || !isset($paymentData['reference'])) {
            throw new \InvalidArgumentException('Missing required payment data: amount_paid, payment_method, and reference are required');
        }

        // Validate amount doesn't exceed invoice total
        if ($paymentData['amount_paid'] > $invoice->total_amount) {
            throw new \InvalidArgumentException('Payment amount cannot exceed invoice total');
        }

        $paymentLog = PaymentLog::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => $paymentData['amount_paid'],
            'currency' => $paymentData['currency'] ?? 'GBP',
            'payment_method' => $paymentData['payment_method'],
            'payment_date' => $paymentData['payment_date'] ?? now(),
            'reference' => $paymentData['reference'],
            'notes' => $paymentData['notes'] ?? null,
            'status' => 'pending_confirmation',
            'logged_by_id' => auth()->id() ?? $paymentData['logged_by_id'] ?? null, // Fallback for CLI/queue usage
        ]);

        // If payment covers full amount, mark invoice as partially paid
        if ($paymentData['amount_paid'] < $invoice->total_amount) {
            $invoice->update(['status' => 'partially_paid']);
        }

        event(new PaymentLogged($paymentLog));

        return $paymentLog;
    }

    public function confirmPayment(PaymentLog $paymentLog, User $confirmedBy): PaymentLog
    {
        if ($paymentLog->status === 'confirmed') {
            throw new \Exception('Payment has already been confirmed');
        }

        $paymentLog->update([
            'status' => 'confirmed',
            'confirmed_by_id' => $confirmedBy->id,
            'confirmed_at' => now(),
        ]);

        $invoice = $paymentLog->invoice;

        // Check if this payment completes the invoice
        $totalPaid = PaymentLog::where('invoice_id', $invoice->id)
            ->where('status', 'confirmed')
            ->sum('amount_paid');

        if ($totalPaid >= $invoice->total_amount) {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        } else {
            $invoice->update([
                'status' => 'partially_paid',
            ]);
        }

        event(new PaymentConfirmed($paymentLog));

        return $paymentLog->fresh();
    }

    public function rejectPayment(PaymentLog $paymentLog, string $reason): PaymentLog
    {
        $paymentLog->update([
            'status' => 'rejected',
            'notes' => $paymentLog->notes . "\nRejection reason: " . $reason,
        ]);

        return $paymentLog->fresh();
    }

    public function getPendingPayments(): Collection
    {
        return PaymentLog::where('status', 'pending_confirmation')
            ->with(['invoice', 'loggedBy', 'confirmedBy'])
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    public function getPaymentHistoryForEntity(string $entityType, int $entityId): Collection
    {
        return PaymentLog::whereHas('invoice', function ($query) use ($entityType, $entityId) {
            $query->where('from_type', $entityType)
                  ->where('from_id', $entityId);
        })
        ->with(['invoice', 'loggedBy', 'confirmedBy'])
        ->orderBy('payment_date', 'desc')
        ->get();
    }

    public function getPaymentHistoryForInvoice(Invoice $invoice): Collection
    {
        return PaymentLog::where('invoice_id', $invoice->id)
            ->with(['loggedBy', 'confirmedBy'])
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    public function getTotalPaidForInvoice(Invoice $invoice): float
    {
        return PaymentLog::where('invoice_id', $invoice->id)
            ->where('status', 'confirmed')
            ->sum('amount_paid');
    }

    public function isInvoiceFullyPaid(Invoice $invoice): bool
    {
        $totalPaid = $this->getTotalPaidForInvoice($invoice);
        return $totalPaid >= $invoice->total_amount;
    }
}
