<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class AgencyEmployee extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'branch_id',
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
        'max_weekly_hours' => 'integer',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(AgencyBranch::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function shiftOffers(): HasMany
    {
        return $this->hasMany(ShiftOffer::class);
    }

    public function timeOffRequests(): HasMany
    {
        return $this->hasMany(TimeOffRequest::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForAgency($query, $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeForAgencies(Builder $query, array $agencyIds): Builder
    {
        return $query->whereIn('agency_id', $agencyIds);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeVisibleToAgency(Builder $query, int $agencyId): Builder
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeVisibleToAgent(Builder $query, int $agentId): Builder
    {
        $agent = Agent::find($agentId);
        return $query->where('agency_id', $agent->agency_id);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isTerminated(): bool
    {
        return $this->status === 'terminated';
    }

    public function isContractActive(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        $now = now();

        if ($this->contract_start_date && $this->contract_start_date->gt($now)) {
            return false;
        }

        if ($this->contract_end_date && $this->contract_end_date->lt($now)) {
            return false;
        }

        return true;
    }

    public function hasActiveAssignments(): bool
    {
        return $this->assignments()->active()->exists();
    }

    public function canAcceptNewAssignments(): bool
    {
        return $this->isContractActive() &&
            !$this->isSuspended() &&
            !$this->isTerminated();
    }

    public function getActiveAssignmentsCount(): int
    {
        return $this->assignments()->active()->count();
    }

    public function getWeeklyScheduledHours(): float
    {
        return $this->assignments()
            ->active()
            ->with('shifts')
            ->get()
            ->sum(function ($assignment) {
                return $assignment->shifts
                    ->where('status', 'scheduled')
                    ->sum(function ($shift) {
                        return $shift->start_time->diffInHours($shift->end_time);
                    });
            });
    }

    public function isUnderMaxWeeklyHours(): bool
    {
        if (!$this->max_weekly_hours) {
            return true;
        }

        return $this->getWeeklyScheduledHours() < $this->max_weekly_hours;
    }

    public function hasSpecialization(string $specialization): bool
    {
        return in_array($specialization, $this->specializations ?? []);
    }

    public function prefersLocation($locationId): bool
    {
        return in_array($locationId, $this->preferred_locations ?? []);
    }
}
