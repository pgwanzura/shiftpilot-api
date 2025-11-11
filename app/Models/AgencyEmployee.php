<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgencyEmployee extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'employee_id',
        'position',
        'pay_rate',
        'employment_type',
        'status',
        'contract_start_date',
        'contract_end_date',
        'specializations',
        'preferred_locations',
        'max_weekly_hours',
        'notes',
        'meta',
    ];

    protected $casts = [
        'pay_rate' => 'decimal:2',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'specializations' => 'array',
        'preferred_locations' => 'array',
        'meta' => 'array',
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function shiftOffers()
    {
        return $this->hasMany(ShiftOffer::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isContractActive()
    {
        return $this->isActive() &&
            (!$this->contract_end_date || $this->contract_end_date >= now());
    }
}
