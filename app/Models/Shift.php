<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id',
        'agency_id',
        'placement_id',
        'employee_id',
        'agent_id',
        'location_id',
        'start_time',
        'end_time',
        'hourly_rate',
        'status',
        'created_by_type',
        'created_by_id',
        'meta',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'hourly_rate' => 'decimal:2',
        'meta' => 'array',
    ];

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function timesheet(): HasOne
    {
        return $this->hasOne(Timesheet::class);
    }

    public function shiftApprovals(): HasMany
    {
        return $this->hasMany(ShiftApproval::class);
    }

    public function shiftOffers(): HasMany
    {
        return $this->hasMany(ShiftOffer::class);
    }

    public function createdBy()
    {
        return $this->morphTo();
    }
}
