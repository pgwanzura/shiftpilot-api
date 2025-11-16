<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_employee_id',
        'period_start',
        'period_end',
        'total_hours',
        'gross_pay',
        'taxes',
        'deductions',
        'net_pay',
        'status',
        'paid_at',
        'payout_id',
        'payment_reference',
        'breakdown',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_hours' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'taxes' => 'decimal:2',
        'deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'paid_at' => 'datetime',
        'breakdown' => 'array',
    ];

    public function agencyEmployee(): BelongsTo
    {
        return $this->belongsTo(AgencyEmployee::class);
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('status', 'processing');
    }

    public function scopeForPeriod(Builder $query, string $start, string $end): Builder
    {
        return $query->where('period_start', $start)->where('period_end', $end);
    }

    public function scopeVisibleToAgency(Builder $query, int $agencyId): Builder
    {
        return $query->whereHas('agencyEmployee', function (Builder $q) use ($agencyId) {
            $q->where('agency_id', $agencyId);
        });
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function markAsPaid(string $paymentReference, int $payoutId): bool
    {
        return $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => $paymentReference,
            'payout_id' => $payoutId,
        ]);
    }

    public function calculateNetPay(): float
    {
        return $this->gross_pay - $this->taxes - $this->deductions;
    }

    public function validateNetPay(): bool
    {
        return $this->net_pay === $this->calculateNetPay();
    }
}
