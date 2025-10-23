<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformBilling extends Model
{
    use HasFactory;

    protected $table = 'platform_billing';

    protected $fillable = [
        'commission_rate',
        'transaction_fee_flat',
        'transaction_fee_percent',
        'payout_schedule_days',
        'tax_vat_rate_percent'
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'transaction_fee_flat' => 'decimal:2',
        'transaction_fee_percent' => 'decimal:2',
        'tax_vat_rate_percent' => 'decimal:2'
    ];

    /**
     * Get the singleton instance of platform billing settings
     */
    public static function settings(): self
    {
        return static::firstOrCreate([], [
            'commission_rate' => 2.00,
            'transaction_fee_flat' => 0.30,
            'transaction_fee_percent' => 2.90,
            'payout_schedule_days' => 7,
            'tax_vat_rate_percent' => 0.00,
        ]);
    }

    /**
     * Calculate commission amount for a given total
     */
    public function calculateCommission(float $amount): float
    {
        return $amount * ($this->commission_rate / 100);
    }

    /**
     * Calculate transaction fees for a given amount
     */
    public function calculateTransactionFees(float $amount): float
    {
        return $this->transaction_fee_flat + ($amount * ($this->transaction_fee_percent / 100));
    }

    /**
     * Calculate net amount after commission and fees
     */
    public function calculateNetAmount(float $amount): float
    {
        $commission = $this->calculateCommission($amount);
        $fees = $this->calculateTransactionFees($amount);

        return $amount - $commission - $fees;
    }

    /**
     * Get payout date based on schedule
     */
    public function getPayoutDate(): \Carbon\Carbon
    {
        return now()->addDays($this->payout_schedule_days);
    }
}
