<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    public function agencyEmployee(): BelongsTo
    {
        return $this->belongsTo(AgencyEmployee::class, 'agency_employee_id');
    }

    public function payout()
    {
        return $this->belongsTo(Payout::class);
    }
}
