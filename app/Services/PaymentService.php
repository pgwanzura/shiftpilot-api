<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function getPayments(array $filters = []): LengthAwarePaginator
    {
        $query = Payment::with(['invoice']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['method'])) {
            $query->where('method', $filters['method']);
        }

        if (isset($filters['payer_type'])) {
            $query->where('payer_type', $filters['payer_type']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('processor_id', 'like', "%{$search}%")
                  ->orWhereHas('invoice', function ($q) use ($search) {
                      $q->where('reference', 'like', "%{$search}%");
                  });
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function refundPayment(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            $refund = Payment::create([
                'invoice_id' => $payment->invoice_id,
                'payer_type' => $payment->payer_type,
                'payer_id' => $payment->payer_id,
                'amount' => -$payment->amount,
                'method' => $payment->method,
                'status' => 'refunded',
                'fee_amount' => $payment->fee_amount,
                'net_amount' => -$payment->net_amount,
                'metadata' => [
                    'original_payment_id' => $payment->id,
                    'refund_reason' => 'requested_by_user'
                ]
            ]);

            $payment->update(['status' => 'refunded']);

            $payment->invoice->update(['status' => 'refunded']);

            return $refund;
        });
    }
}
