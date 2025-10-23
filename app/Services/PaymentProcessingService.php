<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentProcessingService
{
    public function __construct(
        private StripeService $stripeService,
        private PaymentLoggingService $paymentLoggingService
    ) {
    }

    public function createPaymentIntent(Invoice $invoice): array
    {
        $user = auth()->user();
        return $this->stripeService->createPaymentIntent($invoice, $user);
    }

    public function processPayment(Invoice $invoice, array $data): Payment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $user = auth()->user();
            $payerType = $this->getPayerType($user);
            $payerId = $this->getPayerId($user);

            if ($data['amount'] > $invoice->total_amount) {
                throw new \Exception('Payment amount cannot exceed invoice total');
            }

            $paymentResult = $this->processWithProvider($invoice, $data, $user);

            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'payer_type' => $payerType,
                'payer_id' => $payerId,
                'amount' => $data['amount'],
                'method' => $data['method'],
                'processor_id' => $paymentResult['processor_id'],
                'status' => $paymentResult['status'],
                'fee_amount' => $paymentResult['fee_amount'] ?? 0,
                'net_amount' => $data['amount'] - ($paymentResult['fee_amount'] ?? 0),
                'metadata' => $paymentResult['metadata'] ?? [],
            ]);

            if ($paymentResult['status'] === 'completed') {
                $this->handleSuccessfulPayment($invoice, $payment);
            }

            return $payment;
        });
    }

    private function processWithProvider(Invoice $invoice, array $data, User $user): array
    {
        switch ($data['method']) {
            case 'stripe':
                return $this->stripeService->processPayment($invoice, $data, $user);
            case 'bacs':
                return $this->processBacsPayment($invoice, $data);
            case 'sepa':
                return $this->processSepaPayment($invoice, $data);
            case 'paypal':
                return $this->processPaypalPayment($invoice, $data);
            default:
                throw new \Exception('Unsupported payment method');
        }
    }

    private function processBacsPayment(Invoice $invoice, array $data): array
    {
        return [
            'processor_id' => 'BACS_' . uniqid(),
            'status' => 'pending',
            'fee_amount' => 0,
            'metadata' => [
                'bacs_reference' => 'BACS' . time(),
                'pending_confirmation' => true
            ]
        ];
    }

    private function processSepaPayment(Invoice $invoice, array $data): array
    {
        return [
            'processor_id' => 'SEPA_' . uniqid(),
            'status' => 'pending',
            'fee_amount' => 0.25,
            'metadata' => [
                'sepa_mandate_id' => 'MDT' . time(),
                'pending_confirmation' => true
            ]
        ];
    }

    private function processPaypalPayment(Invoice $invoice, array $data): array
    {
        return [
            'processor_id' => 'PAYPAL_' . uniqid(),
            'status' => 'completed',
            'fee_amount' => $data['amount'] * 0.029 + 0.30,
            'metadata' => [
                'paypal_order_id' => 'PPO' . time()
            ]
        ];
    }

    private function handleSuccessfulPayment(Invoice $invoice, Payment $payment): void
    {
        $totalPaid = Payment::where('invoice_id', $invoice->id)
            ->where('status', 'completed')
            ->sum('amount');

        if ($totalPaid >= $invoice->total_amount) {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_reference' => $payment->processor_id,
            ]);
        } else {
            $invoice->update([
                'status' => 'partially_paid',
            ]);
        }

        $this->paymentLoggingService->logPlatformPayment($invoice, [
            'amount_paid' => $payment->amount,
            'payment_method' => $payment->method,
            'reference' => $payment->processor_id,
            'currency' => 'GBP',
        ]);
    }

    private function getPayerType(User $user): string
    {
        return match($user->role) {
            'employer_admin' => 'employer',
            'agency_admin', 'agent' => 'agency',
            default => 'user'
        };
    }

    private function getPayerId(User $user): int
    {
        return match($user->role) {
            'employer_admin' => $user->employer->id,
            'agency_admin', 'agent' => $user->agency->id,
            default => $user->id
        };
    }
}
