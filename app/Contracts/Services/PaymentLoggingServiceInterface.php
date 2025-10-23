<?php

namespace App\Contracts\Services;

use App\Models\Invoice;
use App\Models\PaymentLog;

interface PaymentLoggingServiceInterface
{
    public function logPlatformPayment(Invoice $invoice, array $paymentData): PaymentLog;
    public function confirmPayment(PaymentLog $paymentLog, User $confirmedBy): void;
    public function getPendingPayments(): Collection;
}
