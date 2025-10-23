<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function getInvoices(array $filters = []): LengthAwarePaginator
    {
        $query = Invoice::with(['from', 'to']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['from_type'])) {
            $query->where('from_type', $filters['from_type']);
        }

        if (isset($filters['to_type'])) {
            $query->where('to_type', $filters['to_type']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhereHas('from', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('to', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function getInvoicePayments(Invoice $invoice): LengthAwarePaginator
    {
        return $invoice->payments()->latest()->paginate(15);
    }

    public function markAsPaid(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            return $invoice->fresh();
        });
    }

    public function cancelInvoice(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $invoice->update([
                'status' => 'cancelled',
            ]);

            return $invoice->fresh();
        });
    }
}
