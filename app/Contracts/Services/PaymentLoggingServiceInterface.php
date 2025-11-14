<?php

namespace App\Contracts\Services;

use App\Models\Invoice;
use App\Models\PaymentLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface PaymentLoggingServiceInterface
{
    public function logPlatformPayment(Invoice $invoice, array $paymentData): PaymentLog;
    public function confirmPayment(PaymentLog $paymentLog, User $confirmedBy): void;
    public function rejectPayment(PaymentLog $paymentLog, User $rejectedBy, string $reason): void;
    public function getPendingPayments(): Collection;
    public function getPaymentHistoryForEntity(string $entityType, int $entityId): Collection;
    public function getPaymentHistoryForInvoice(Invoice $invoice): Collection;
    public function getTotalPaidForInvoice(Invoice $invoice): float;
    public function isInvoiceFullyPaid(Invoice $invoice): bool;
    public function getInvoiceOutstandingAmount(Invoice $invoice): float;
}
