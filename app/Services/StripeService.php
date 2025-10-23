<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;

class StripeService
{
    private \Stripe\StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
    }

    public function processPayment(Invoice $invoice, array $data, User $user): array
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $this->convertToCents($data['amount']),
                'currency' => 'gbp',
                'payment_method' => $data['payment_method_id'],
                'confirm' => true,
                'return_url' => config('app.url') . '/payment/success',
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'user_id' => $user->id,
                ],
            ]);

            $feeAmount = 0;
            if ($paymentIntent->charges && count($paymentIntent->charges->data) > 0) {
                $charge = $paymentIntent->charges->data[0];
                $feeAmount = $charge->fee / 100;
            }

            return [
                'processor_id' => $paymentIntent->id,
                'status' => $paymentIntent->status === 'succeeded' ? 'completed' : $paymentIntent->status,
                'fee_amount' => $feeAmount,
                'metadata' => [
                    'stripe_payment_intent' => $paymentIntent->id,
                    'stripe_charge_id' => $paymentIntent->charges->data[0]->id ?? null,
                ]
            ];

        } catch (\Stripe\Exception\CardException $e) {
            throw new \Exception('Payment failed: ' . $e->getError()->message);
        } catch (\Exception $e) {
            throw new \Exception('Payment processing error: ' . $e->getMessage());
        }
    }

    public function createPaymentIntent(Invoice $invoice, User $user): array
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $this->convertToCents($invoice->total_amount),
                'currency' => 'gbp',
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'user_id' => $user->id,
                ],
            ]);

            return [
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
            ];

        } catch (\Exception $e) {
            throw new \Exception('Failed to create payment intent: ' . $e->getMessage());
        }
    }

    public function refundPayment(string $paymentIntentId, ?float $amount = null): array
    {
        try {
            $refundData = ['payment_intent' => $paymentIntentId];
            if ($amount) {
                $refundData['amount'] = $this->convertToCents($amount);
            }

            $refund = $this->stripe->refunds->create($refundData);

            return [
                'refund_id' => $refund->id,
                'status' => $refund->status,
                'amount' => $refund->amount / 100,
            ];

        } catch (\Exception $e) {
            throw new \Exception('Refund failed: ' . $e->getMessage());
        }
    }

    private function convertToCents(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
