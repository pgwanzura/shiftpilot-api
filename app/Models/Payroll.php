<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'employee_id',
        'period_start',
        'period_end',
        'total_hours',
        'gross_pay',
        'taxes',
        'net_pay',
        'status',
        'paid_at',
        'payout_id',
    ];

    protected $casts = [
        'total_hours' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'taxes' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'paid_at' => 'datetime',
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function payout()
    {
        return $this->belongsTo(Payout::class);
    }
}
