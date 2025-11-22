<?php

namespace App\Models;

use App\Enums\AssignmentStatus;
use App\Enums\AssignmentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

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
        'assignment_type',
        'shift_pattern',
        'notes',
        'created_by_id',
    ];

    protected $guarded = [
        'markup_amount',
        'markup_percent',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'shift_pattern' => 'array',
        'agreed_rate' => 'decimal:2',
        'pay_rate' => 'decimal:2',
        'markup_amount' => 'decimal:2',
        'markup_percent' => 'decimal:2',
        'expected_hours_per_week' => 'decimal:2',
        'status' => AssignmentStatus::class,
        'assignment_type' => AssignmentType::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (Assignment $assignment) {
            if (empty($assignment->status)) {
                $assignment->status = AssignmentStatus::PENDING;
            }
            $assignment->calculateAndSetMarkup();
        });

        static::updating(function (Assignment $assignment) {
            if ($assignment->isDirty(['agreed_rate', 'pay_rate'])) {
                $assignment->calculateAndSetMarkup();
            }
        });
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

    public function employee(): HasOneThrough
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

    public function agency(): HasOneThrough
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

    public function employer(): HasOneThrough
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

    public function canBeUpdated(): bool
    {
        return !in_array($this->status, [
            AssignmentStatus::COMPLETED,
            AssignmentStatus::CANCELLED,
        ], true);
    }

    public function canBeDeleted(): bool
    {
        return $this->isPending() && !$this->shifts()->exists();
    }

    public function canBeCompleted(): bool
    {
        return $this->isActive() &&
            ($this->end_date === null || $this->end_date->isPast());
    }

    public function canBeSuspended(): bool
    {
        return $this->isActive();
    }

    public function canBeReactivated(): bool
    {
        return $this->isSuspended();
    }

    public function isValidStatusTransition(AssignmentStatus $toStatus): bool
    {
        $allowedTransitions = [
            AssignmentStatus::PENDING->value => [
                AssignmentStatus::ACTIVE->value,
                AssignmentStatus::CANCELLED->value
            ],
            AssignmentStatus::ACTIVE->value => [
                AssignmentStatus::COMPLETED->value,
                AssignmentStatus::SUSPENDED->value,
                AssignmentStatus::CANCELLED->value
            ],
            AssignmentStatus::SUSPENDED->value => [
                AssignmentStatus::ACTIVE->value,
                AssignmentStatus::CANCELLED->value
            ],
            AssignmentStatus::COMPLETED->value => [],
            AssignmentStatus::CANCELLED->value => [],
        ];

        return in_array(
            $toStatus->value,
            $allowedTransitions[$this->status->value] ?? [],
            true
        );
    }

    public function hasValidRates(): bool
    {
        return $this->agreed_rate >= $this->pay_rate;
    }

    public function belongsToAgency(int $agencyId): bool
    {
        return $this->relationLoaded('agencyEmployee') &&
            $this->agencyEmployee?->agency_id === $agencyId;
    }

    public function belongsToEmployer(int $employerId): bool
    {
        return $this->relationLoaded('contract') &&
            $this->contract?->employer_id === $employerId;
    }

    public function belongsToEmployee(int $employeeId): bool
    {
        return $this->relationLoaded('agencyEmployee') &&
            $this->agencyEmployee?->employee_id === $employeeId;
    }

    public function updateStatus(AssignmentStatus $status): void
    {
        $this->forceFill(['status' => $status])->save();
    }

    public function extendEndDate(\DateTime $newEndDate): void
    {
        $this->forceFill(['end_date' => $newEndDate])->save();
    }

    public function getAnalyticsData(): array
    {
        $totalEarnings = $this->timesheets()
            ->get()
            ->sum(fn($timesheet) => $timesheet->hours_worked * $timesheet->shift->hourly_rate);

        $totalHoursWorked = $this->timesheets()->sum('hours_worked');

        return [
            'total_shifts' => $this->shifts()->count(),
            'completed_shifts' => $this->shifts()->where('status', 'completed')->count(),
            'total_hours_worked' => $totalHoursWorked,
            'total_earnings' => $totalEarnings,
            'utilization_rate' => $this->calculateUtilizationRate($totalHoursWorked),
        ];
    }

    protected function durationDays(): Attribute
    {
        return Attribute::make(
            get: fn(): ?int => $this->start_date && $this->end_date
                ? $this->start_date->diffInDays($this->end_date)
                : null
        );
    }

    protected function isOngoing(): Attribute
    {
        return Attribute::make(
            get: fn(): bool => $this->end_date === null && $this->isActive()
        );
    }

    protected function totalExpectedHours(): Attribute
    {
        return Attribute::make(
            get: function (): ?float {
                if (!$this->expected_hours_per_week || !$this->durationDays) {
                    return null;
                }
                return ($this->expected_hours_per_week / 7) * $this->durationDays;
            }
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', AssignmentStatus::ACTIVE);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', AssignmentStatus::PENDING);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', AssignmentStatus::COMPLETED);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', AssignmentStatus::CANCELLED);
    }

    public function scopeSuspended(Builder $query): Builder
    {
        return $query->where('status', AssignmentStatus::SUSPENDED);
    }

    public function scopeForAgency(Builder $query, int $agencyId): Builder
    {
        return $query->whereHas(
            'agencyEmployee',
            fn(Builder $q) =>
            $q->where('agency_id', $agencyId)
        );
    }

    public function scopeForEmployer(Builder $query, int $employerId): Builder
    {
        return $query->whereHas(
            'contract',
            fn(Builder $q) =>
            $q->where('employer_id', $employerId)
        );
    }

    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->whereHas(
            'agencyEmployee',
            fn(Builder $q) =>
            $q->where('employee_id', $employeeId)
        );
    }

    public function scopeDateRange(Builder $query, string $startDate, ?string $endDate = null): Builder
    {
        return $query->where(function (Builder $q) use ($startDate, $endDate) {
            $q->where('start_date', '<=', $endDate ?? $startDate)
                ->where(function (Builder $q2) use ($startDate) {
                    $q2->whereNull('end_date')
                        ->orWhere('end_date', '>=', $startDate);
                });
        });
    }

    public function scopeCurrent(Builder $query): Builder
    {
        $now = now();
        return $query->where('start_date', '<=', $now)
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $now);
            });
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('end_date', '<', now())
            ->where('status', AssignmentStatus::ACTIVE);
    }

    public function scopeWithShifts(Builder $query): Builder
    {
        return $query->has('shifts');
    }

    public function scopeWithoutShifts(Builder $query): Builder
    {
        return $query->doesntHave('shifts');
    }

    protected function calculateAndSetMarkup(): void
    {
        if ($this->agreed_rate && $this->pay_rate) {
            $this->markup_amount = $this->agreed_rate - $this->pay_rate;
            $this->markup_percent = $this->pay_rate > 0
                ? ($this->markup_amount / $this->pay_rate) * 100
                : 0;
        }
    }

    protected function calculateUtilizationRate(float $actualHours): float
    {
        $expectedHours = $this->totalExpectedHours;

        if (!$expectedHours || $expectedHours <= 0) {
            return 0;
        }

        return min(100, ($actualHours / $expectedHours) * 100);
    }
}
