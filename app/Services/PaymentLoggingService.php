<?php

namespace App\Services;

use App\Contracts\Services\PaymentLoggingServiceInterface;
use App\Models\PaymentLog;
use App\Models\Invoice;
use App\Models\User;
use App\Events\PaymentLogged;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PaymentLoggingService implements PaymentLoggingServiceInterface
{
    public function logPlatformPayment(Invoice $invoice, array $paymentData): PaymentLog
    {
        $this->validatePaymentData($paymentData);
        $this->validatePaymentAmount($invoice, $paymentData['amount_paid']);

        return DB::transaction(function () use ($invoice, $paymentData) {
            $paymentLog = PaymentLog::create([
                'invoice_id' => $invoice->id,
                'amount_paid' => $paymentData['amount_paid'],
                'currency' => $paymentData['currency'] ?? 'GBP',
                'payment_method' => $paymentData['payment_method'],
                'payment_date' => $paymentData['payment_date'] ?? now(),
                'reference' => $paymentData['reference'],
                'notes' => $paymentData['notes'] ?? null,
                'status' => 'pending_confirmation',
                'logged_by_id' => $this->resolveLoggedById($paymentData),
            ]);

            $this->updateInvoiceStatus($invoice);

            event(new PaymentLogged($paymentLog));

            return $paymentLog;
        });
    }

    public function confirmPayment(PaymentLog $paymentLog, User $confirmedBy): void
    {
        if ($paymentLog->status === 'confirmed') {
            throw new \InvalidArgumentException('Payment has already been confirmed');
        }

        DB::transaction(function () use ($paymentLog, $confirmedBy) {
            $paymentLog->update([
                'status' => 'confirmed',
                'confirmed_by_id' => $confirmedBy->id,
                'confirmed_at' => now(),
            ]);

            $this->updateInvoiceStatus($paymentLog->invoice);
        });
    }

    public function rejectPayment(PaymentLog $paymentLog, User $rejectedBy, string $reason): void
    {
        DB::transaction(function () use ($paymentLog, $rejectedBy, $reason) {
            $paymentLog->update([
                'status' => 'rejected',
                'confirmed_by_id' => $rejectedBy->id,
                'confirmed_at' => now(),
                'notes' => trim($paymentLog->notes . "\nRejection reason: " . $reason),
            ]);
        });
    }

    public function getPendingPayments(): Collection
    {
        return PaymentLog::with(['invoice', 'loggedBy', 'confirmedBy'])
            ->where('status', 'pending_confirmation')
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
        return (float) PaymentLog::where('invoice_id', $invoice->id)
            ->where('status', 'confirmed')
            ->sum('amount_paid');
    }

    public function isInvoiceFullyPaid(Invoice $invoice): bool
    {
        $totalPaid = $this->getTotalPaidForInvoice($invoice);
        return $totalPaid >= $invoice->total_amount;
    }

    public function getInvoiceOutstandingAmount(Invoice $invoice): float
    {
        $totalPaid = $this->getTotalPaidForInvoice($invoice);
        return max(0, $invoice->total_amount - $totalPaid);
    }

    private function validatePaymentData(array $paymentData): void
    {
        $requiredFields = ['amount_paid', 'payment_method', 'reference'];

        foreach ($requiredFields as $field) {
            if (!isset($paymentData[$field]) || empty($paymentData[$field])) {
                throw new \InvalidArgumentException("Missing required payment data: {$field}");
            }
        }

        if (!is_numeric($paymentData['amount_paid']) || $paymentData['amount_paid'] <= 0) {
            throw new \InvalidArgumentException('Payment amount must be a positive number');
        }
    }

    private function validatePaymentAmount(Invoice $invoice, float $amountPaid): void
    {
        if ($amountPaid > $invoice->total_amount) {
            throw new \InvalidArgumentException('Payment amount cannot exceed invoice total');
        }

        $outstandingAmount = $this->getInvoiceOutstandingAmount($invoice);
        $potentialTotalPaid = $outstandingAmount + $amountPaid;

        if ($potentialTotalPaid > $invoice->total_amount) {
            throw new \InvalidArgumentException('Payment would result in overpayment');
        }
    }

    private function updateInvoiceStatus(Invoice $invoice): void
    {
        $totalPaid = $this->getTotalPaidForInvoice($invoice);

        if ($totalPaid >= $invoice->total_amount) {
            $status = 'paid';
        } elseif ($totalPaid > 0) {
            $status = 'partially_paid';
        } else {
            $status = 'pending';
        }

        $updateData = ['status' => $status];

        if ($status === 'paid') {
            $updateData['paid_at'] = now();
        }

        $invoice->update($updateData);
    }

    private function resolveLoggedById(array $paymentData): ?int
    {
        return auth()->id() ?? $paymentData['logged_by_id'] ?? null;
    }
}
