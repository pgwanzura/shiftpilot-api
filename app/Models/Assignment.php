<?php

namespace App\Models;

use App\Enums\AssignmentStatus;
use App\Enums\AssignmentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\DB;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'agency_employee_id',
        'shift_request_id',
        'agency_response_id',
        'location_id',
        'role',
        'start_date',
        'end_date',
        'expected_hours_per_week',
        'agreed_rate',
        'pay_rate',
        'markup_amount',
        'markup_percent',
        'status',
        'assignment_type',
        'shift_pattern',
        'notes',
        'created_by_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'shift_pattern' => 'array',
        'agreed_rate' => 'decimal:2',
        'pay_rate' => 'decimal:2',
        'markup_amount' => 'decimal:2',
        'markup_percent' => 'decimal:2',
        'status' => AssignmentStatus::class,
        'assignment_type' => AssignmentType::class,
    ];

    protected $appends = [
        'duration_days',
        'is_ongoing',
        'total_expected_hours',
        'is_direct_assignment',
        'is_standard_assignment',
    ];

    protected static function booted()
    {
        static::creating(function ($assignment) {
            $assignment->calculateMarkup();
            $assignment->validateAssignmentCreation();
        });

        static::updating(function ($assignment) {
            if ($assignment->isDirty(['agreed_rate', 'pay_rate'])) {
                $assignment->calculateMarkup();
            }
            $assignment->validateAssignmentUpdate();
        });
    }

    public function calculateMarkup(): void
    {
        if ($this->agreed_rate && $this->pay_rate) {
            $this->markup_amount = $this->agreed_rate - $this->pay_rate;
            $this->markup_percent = $this->pay_rate > 0 ? ($this->markup_amount / $this->pay_rate) * 100 : 0;
        }
    }

    public function validateAssignmentCreation(): void
    {
        if (!$this->status) {
            $this->status = AssignmentStatus::PENDING;
        }

        if (!$this->validateRates()) {
            throw new \InvalidArgumentException('Agreed rate cannot be less than pay rate');
        }

        if (!$this->hasActiveContract()) {
            throw new \InvalidArgumentException('Assignment requires an active contract');
        }

        if (!$this->hasActiveAgencyEmployee()) {
            throw new \InvalidArgumentException('Assignment requires an active agency employee');
        }

        if ($this->assignment_type === AssignmentType::STANDARD && !$this->agency_response_id) {
            throw new \InvalidArgumentException('Standard assignments require an agency response');
        }

        if ($this->hasOverlappingAssignments()) {
            throw new \InvalidArgumentException('Employee has overlapping assignments');
        }
    }

    public function validateAssignmentUpdate(): void
    {
        if ($this->isDirty('agreed_rate') && $this->agreed_rate < $this->pay_rate) {
            throw new \InvalidArgumentException('Agreed rate cannot be less than pay rate');
        }

        if ($this->isDirty('status') && !$this->canChangeStatus($this->getOriginal('status'), $this->status)) {
            throw new \InvalidArgumentException('Invalid assignment status transition');
        }
    }

    public function hasOverlappingAssignments(): bool
    {
        return self::where('agency_employee_id', $this->agency_employee_id)
            ->where('id', '!=', $this->id)
            ->whereIn('status', AssignmentStatus::activeStates())
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $this->start_date);
            })
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('start_date', '<=', $this->end_date ?? '9999-12-31');
            })
            ->exists();
    }

    public function loadRelations(): self
    {
        return $this->load([
            'contract.employer',
            'contract.agency',
            'agencyEmployee.employee.user',
            'agencyEmployee.agency',
            'location',
            'shiftRequest',
            'agencyResponse',
            'createdBy',
            'shifts'
        ]);
    }

    public function getAnalyticsData(): array
    {
        $totalEarnings = $this->timesheets()
            ->get()
            ->sum(function ($timesheet) {
                return $timesheet->hours_worked * $timesheet->shift->hourly_rate;
            });

        return [
            'total_shifts' => $this->shifts()->count(),
            'completed_shifts' => $this->shifts()->where('status', 'completed')->count(),
            'total_hours_worked' => $this->timesheets()->sum('hours_worked'),
            'total_earnings' => $totalEarnings,
            'utilization_rate' => $this->calculateUtilizationRate(),
        ];
    }

    private function calculateUtilizationRate(): float
    {
        $expectedHours = $this->total_expected_hours;
        $actualHours = $this->timesheets()->sum('hours_worked');

        return $expectedHours && $expectedHours > 0 ? min(100, ($actualHours / $expectedHours) * 100) : 0;
    }

    public function isActive(): bool
    {
        return $this->status === AssignmentStatus::ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->status === AssignmentStatus::COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === AssignmentStatus::PENDING;
    }

    public function isCancelled(): bool
    {
        return $this->status === AssignmentStatus::CANCELLED;
    }

    public function isSuspended(): bool
    {
        return $this->status === AssignmentStatus::SUSPENDED;
    }

    public function isDirectAssignment(): bool
    {
        return $this->assignment_type === AssignmentType::DIRECT;
    }

    public function isStandardAssignment(): bool
    {
        return $this->assignment_type === AssignmentType::STANDARD;
    }

    public function canBeUpdated(): bool
    {
        return !in_array($this->status, [
            AssignmentStatus::COMPLETED,
            AssignmentStatus::CANCELLED,
        ]);
    }

    public function canBeDeleted(): bool
    {
        return $this->isPending() && !$this->shifts()->exists();
    }

    public function canBeCompleted(): bool
    {
        return $this->isActive() && ($this->end_date?->isPast() || $this->end_date === null);
    }

    public function canBeSuspended(): bool
    {
        return $this->isActive();
    }

    public function canBeReactivated(): bool
    {
        return $this->isSuspended();
    }

    public function canChangeStatus(string $fromStatus, string $toStatus): bool
    {
        $allowedTransitions = [
            AssignmentStatus::PENDING => [AssignmentStatus::ACTIVE, AssignmentStatus::CANCELLED],
            AssignmentStatus::ACTIVE => [AssignmentStatus::COMPLETED, AssignmentStatus::SUSPENDED, AssignmentStatus::CANCELLED],
            AssignmentStatus::SUSPENDED => [AssignmentStatus::ACTIVE, AssignmentStatus::CANCELLED],
            AssignmentStatus::COMPLETED => [],
            AssignmentStatus::CANCELLED => [],
        ];

        return in_array($toStatus, $allowedTransitions[$fromStatus] ?? []);
    }

    public function validateRates(): bool
    {
        return $this->agreed_rate >= $this->pay_rate;
    }

    public function hasActiveContract(): bool
    {
        return $this->contract && $this->contract->status === 'active';
    }

    public function hasActiveAgencyEmployee(): bool
    {
        return $this->agencyEmployee && $this->agencyEmployee->status === 'active';
    }

    public function hasShifts(): bool
    {
        return $this->shifts()->exists();
    }

    public function hasTimesheets(): bool
    {
        return $this->timesheets()->exists();
    }

    protected function durationDays(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->start_date) {
                    return null;
                }
                return $this->end_date ? $this->start_date->diffInDays($this->end_date) : null;
            }
        );
    }

    protected function isOngoing(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->end_date === null && $this->isActive()
        );
    }

    protected function totalExpectedHours(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->expected_hours_per_week || !$this->duration_days) {
                    return null;
                }
                return ($this->expected_hours_per_week / 7) * $this->duration_days;
            }
        );
    }

    protected function isDirectAssignmentAppended(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->isDirectAssignment()
        );
    }

    protected function isStandardAssignmentAppended(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->isStandardAssignment()
        );
    }

    public function scopeActive($query)
    {
        return $query->where('status', AssignmentStatus::ACTIVE);
    }

    public function scopePending($query)
    {
        return $query->where('status', AssignmentStatus::PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', AssignmentStatus::COMPLETED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', AssignmentStatus::CANCELLED);
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', AssignmentStatus::SUSPENDED);
    }

    public function scopeDirect($query)
    {
        return $query->where('assignment_type', AssignmentType::DIRECT);
    }

    public function scopeStandard($query)
    {
        return $query->where('assignment_type', AssignmentType::STANDARD);
    }

    public function scopeForAgency($query, $agencyId)
    {
        return $query->whereHas('agencyEmployee', function ($q) use ($agencyId) {
            $q->where('agency_id', $agencyId);
        });
    }

    public function scopeForEmployer($query, $employerId)
    {
        return $query->whereHas('contract', function ($q) use ($employerId) {
            $q->where('employer_id', $employerId);
        });
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->whereHas('agencyEmployee', function ($q) use ($employeeId) {
            $q->where('employee_id', $employeeId);
        });
    }

    public function scopeDateRange($query, $startDate, $endDate = null)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->where('start_date', '<=', $endDate ?? $startDate)
                ->where(function ($q2) use ($startDate) {
                    $q2->whereNull('end_date')
                        ->orWhere('end_date', '>=', $startDate);
                });
        });
    }

    public function scopeCurrent($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('end_date')
                ->orWhere('end_date', '>=', now());
        })->where('start_date', '<=', now());
    }

    public function scopeOverdue($query)
    {
        return $query->where('end_date', '<', now())
            ->where('status', AssignmentStatus::ACTIVE);
    }

    public function scopeWithShifts($query)
    {
        return $query->whereHas('shifts');
    }

    public function scopeWithoutShifts($query)
    {
        return $query->whereDoesntHave('shifts');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(EmployerAgencyContract::class, 'contract_id');
    }

    public function agencyEmployee(): BelongsTo
    {
        return $this->belongsTo(AgencyEmployee::class);
    }

    public function shiftRequest(): BelongsTo
    {
        return $this->belongsTo(ShiftRequest::class);
    }

    public function agencyResponse(): BelongsTo
    {
        return $this->belongsTo(AgencyResponse::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function timesheets(): HasManyThrough
    {
        return $this->hasManyThrough(Timesheet::class, Shift::class);
    }

    public function employee()
    {
        return $this->hasOneThrough(
            Employee::class,
            AgencyEmployee::class,
            'id',
            'id',
            'agency_employee_id',
            'employee_id'
        );
    }

    public function agency()
    {
        return $this->hasOneThrough(
            Agency::class,
            AgencyEmployee::class,
            'id',
            'id',
            'agency_employee_id',
            'agency_id'
        );
    }

    public function employer()
    {
        return $this->hasOneThrough(
            Employer::class,
            EmployerAgencyContract::class,
            'id',
            'id',
            'contract_id',
            'employer_id'
        );
    }

    public function shiftTemplates(): HasMany
    {
        return $this->hasMany(ShiftTemplate::class);
    }
}
