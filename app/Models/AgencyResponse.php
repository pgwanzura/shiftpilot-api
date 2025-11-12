<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AgencyResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_request_id',
        'agency_id',
        'proposed_employee_id',
        'proposed_rate',
        'proposed_start_date',
        'proposed_end_date',
        'terms',
        'estimated_total_hours',
        'status',
        'notes',
        'submitted_by_id',
        'responded_at',
        'employer_decision_by_id',
        'employer_decision_at'
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'employer_decision_at' => 'datetime',
        'proposed_rate' => 'decimal:2',
        'proposed_start_date' => 'date',
        'proposed_end_date' => 'date',
        'estimated_total_hours' => 'integer',
        'terms' => 'string'
    ];

    public function shiftRequest(): BelongsTo
    {
        return $this->belongsTo(ShiftRequest::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function proposedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'proposed_employee_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    public function employerDecisionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_decision_by_id');
    }

    public function assignment(): HasOne
    {
        return $this->hasOne(Assignment::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeForAgency($query, $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeForShiftRequest($query, $shiftRequestId)
    {
        return $query->where('shift_request_id', $shiftRequestId);
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isWithdrawn(): bool
    {
        return $this->status === 'withdrawn';
    }

    public function isCounterOffered(): bool
    {
        return $this->status === 'counter_offered';
    }

    public function canBeAccepted(): bool
    {
        return $this->isPending() || $this->isCounterOffered();
    }

    public function canBeRejected(): bool
    {
        return $this->isPending() || $this->isCounterOffered();
    }

    public function canBeWithdrawn(): bool
    {
        return $this->isPending() || $this->isCounterOffered();
    }

    public function hasAssignment(): bool
    {
        return $this->assignment()->exists();
    }

    public function exceedsMaxHourlyRate(): bool
    {
        return $this->proposed_rate > $this->shiftRequest->max_hourly_rate;
    }

    public function isValidForAssignment(): bool
    {
        if (!$this->isAccepted()) {
            return false;
        }

        if ($this->hasAssignment()) {
            return false;
        }

        if (!$this->agency->hasActiveContractWith($this->shiftRequest->employer_id)) {
            return false;
        }

        if ($this->proposedEmployee && !$this->proposedEmployee->isAvailableForDates($this->proposed_start_date, $this->proposed_end_date)) {
            return false;
        }

        return !$this->exceedsMaxHourlyRate();
    }

    public function accept($decisionBy): bool
    {
        if (!$this->canBeAccepted()) {
            return false;
        }

        return $this->update([
            'status' => 'accepted',
            'employer_decision_by_id' => $decisionBy->id,
            'employer_decision_at' => now()
        ]);
    }

    public function reject($decisionBy): bool
    {
        if (!$this->canBeRejected()) {
            return false;
        }

        return $this->update([
            'status' => 'rejected',
            'employer_decision_by_id' => $decisionBy->id,
            'employer_decision_at' => now()
        ]);
    }

    public function withdraw(): bool
    {
        if (!$this->canBeWithdrawn()) {
            return false;
        }

        return $this->update([
            'status' => 'withdrawn',
            'responded_at' => now()
        ]);
    }

    public function counterOffer(array $attributes): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        return $this->update(array_merge($attributes, [
            'status' => 'counter_offered',
            'responded_at' => now()
        ]));
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($agencyResponse) {
            $agencyResponse->validateProposedRate();
            $agencyResponse->validateAgencyContract();
        });

        static::updating(function ($agencyResponse) {
            if ($agencyResponse->isDirty('status') && $agencyResponse->isAccepted()) {
                $agencyResponse->validateNoExistingAssignment();
            }
        });
    }

    private function validateProposedRate(): void
    {
        if ($this->proposed_rate > $this->shiftRequest->max_hourly_rate) {
            throw new \InvalidArgumentException('Proposed rate cannot exceed shift request maximum hourly rate');
        }
    }

    private function validateAgencyContract(): void
    {
        if (!$this->agency->hasActiveContractWith($this->shiftRequest->employer_id)) {
            throw new \InvalidArgumentException('Agency does not have an active contract with this employer');
        }
    }

    private function validateNoExistingAssignment(): void
    {
        if ($this->assignment()->exists()) {
            throw new \InvalidArgumentException('Assignment already exists for this agency response');
        }
    }
}
