<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Placement extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id',
        'title',
        'description',
        'role_requirements',
        'required_qualifications',
        'experience_level',
        'background_check_required',
        'location_id',
        'location_instructions',
        'start_date',
        'end_date',
        'shift_pattern',
        'recurrence_rules',
        'budget_type',
        'budget_amount',
        'currency',
        'overtime_rules',
        'target_agencies',
        'specific_agency_ids',
        'response_deadline',
        'status',
        'selected_agency_id',
        'selected_employee_id',
        'agreed_rate',
        'created_by_id',
    ];

    protected $casts = [
        'role_requirements' => 'array',
        'required_qualifications' => 'array',
        'specific_agency_ids' => 'array',
        'recurrence_rules' => 'array',
        'overtime_rules' => 'array',
        'budget_amount' => 'decimal:2',
        'agreed_rate' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'response_deadline' => 'datetime',
        'background_check_required' => 'boolean',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_FILLED = 'filled';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';
    const SHIFT_PATTERN_ONE_TIME = 'one_time';
    const SHIFT_PATTERN_RECURRING = 'recurring';
    const SHIFT_PATTERN_ONGOING = 'ongoing';

    const BUDGET_TYPE_HOURLY = 'hourly';
    const BUDGET_TYPE_DAILY = 'daily';
    const BUDGET_TYPE_FIXED = 'fixed';

    const TARGET_AGENCIES_ALL = 'all';
    const TARGET_AGENCIES_SPECIFIC = 'specific';

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function selectedAgency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'selected_agency_id');
    }

    public function selectedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'selected_employee_id');
    }

    public function agencyResponses(): HasMany
    {
        return $this->hasMany(AgencyPlacementResponse::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function createShift(array $shiftData = []): Shift
    {
        return $this->shifts()->create(array_merge([
            'employer_id' => $this->employer_id,
            'agency_id' => $this->selected_agency_id,
            'employee_id' => $this->selected_employee_id,
            'location_id' => $this->location_id,
            'hourly_rate' => $this->agreed_rate ?? $this->budget_amount,
            'status' => 'scheduled',
        ], $shiftData));
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeForAgency($query, $agencyId)
    {
        return $query->where(function ($q) use ($agencyId) {
            $q->where('target_agencies', self::TARGET_AGENCIES_ALL)
                ->orWhereJsonContains('specific_agency_ids', $agencyId);
        });
    }

    public function scopeForEmployer($query, $employerId)
    {
        return $query->where('employer_id', $employerId);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isFilled(): bool
    {
        return $this->status === self::STATUS_FILLED;
    }

    public function canBeActivated(): bool
    {
        return $this->isDraft() && $this->start_date->isFuture();
    }

    public function getRemainingResponseTime(): ?string
    {
        if (!$this->response_deadline) {
            return null;
        }

        return $this->response_deadline->diffForHumans();
    }

    public function hasAgencyResponse($agencyId): bool
    {
        return $this->agencyResponses()
            ->where('agency_id', $agencyId)
            ->exists();
    }
}
