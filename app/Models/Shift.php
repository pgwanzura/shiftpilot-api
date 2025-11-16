<?php

namespace App\Models;

use App\Enums\ShiftStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'location_id',
        'shift_date',
        'start_time',
        'end_time',
        'hourly_rate',
        'status',
        'notes',
        'meta',
    ];

    protected $casts = [
        'shift_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'hourly_rate' => 'decimal:2',
        'status' => ShiftStatus::class,
        'meta' => 'array',
    ];

    protected $appends = [
        'duration_hours',
        'is_past',
        'is_future',
        'is_ongoing',
        'total_earnings',
    ];

    protected static function booted()
    {
        static::creating(function ($shift) {
            if (!$shift->shift_date && $shift->start_time) {
                $shift->shift_date = $shift->start_time->toDateString();
            }

            if (!$shift->hourly_rate && $shift->assignment_id) {
                $shift->hourly_rate = $shift->assignment->pay_rate;
            }
        });

        static::updating(function ($shift) {
            if ($shift->isDirty('start_time') && $shift->start_time) {
                $shift->shift_date = $shift->start_time->toDateString();
            }
        });
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
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

    public function employee()
    {
        return $this->hasOneThrough(
            Employee::class,
            AgencyEmployee::class,
            'id',
            'id',
            'assignment.agency_employee_id',
            'employee_id'
        );
    }

    public function agency()
    {
        return $this->hasOneThrough(
            Agency::class,
            Assignment::class,
            'id',
            'id',
            'assignment_id',
            'agency_id'
        );
    }

    public function employer()
    {
        return $this->hasOneThrough(
            Employer::class,
            Assignment::class,
            'id',
            'id',
            'assignment_id',
            'employer_id'
        );
    }

    public function loadRelations(): self
    {
        return $this->load([
            'assignment.agencyEmployee.employee.user',
            'assignment.contract.employer',
            'assignment.contract.agency',
            'location',
            'timesheet',
            'shiftApprovals.contact',
        ]);
    }

    public function isScheduled(): bool
    {
        return $this->status === ShiftStatus::SCHEDULED;
    }

    public function isInProgress(): bool
    {
        return $this->status === ShiftStatus::IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === ShiftStatus::COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === ShiftStatus::CANCELLED;
    }

    public function isNoShow(): bool
    {
        return $this->status === ShiftStatus::NO_SHOW;
    }

    public function canBeUpdated(): bool
    {
        return in_array($this->status, [ShiftStatus::SCHEDULED, ShiftStatus::IN_PROGRESS]);
    }

    public function canBeCancelled(): bool
    {
        return $this->isScheduled() || $this->isInProgress();
    }

    public function canBeStarted(): bool
    {
        return $this->isScheduled() && $this->start_time->isPast();
    }

    public function canBeCompleted(): bool
    {
        return $this->isInProgress() && $this->end_time->isPast();
    }

    protected function durationHours(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->start_time || !$this->end_time) {
                    return 0;
                }
                return $this->start_time->diffInHours($this->end_time);
            }
        );
    }

    protected function isPast(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->end_time?->isPast() ?? false
        );
    }

    protected function isFuture(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->start_time?->isFuture() ?? false
        );
    }

    protected function isOngoing(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->start_time || !$this->end_time) {
                    return false;
                }
                $now = now();
                return $now->between($this->start_time, $this->end_time);
            }
        );
    }

    protected function totalEarnings(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->timesheet && $this->timesheet->hours_worked) {
                    return $this->timesheet->hours_worked * $this->hourly_rate;
                }
                return $this->duration_hours * $this->hourly_rate;
            }
        );
    }

    public function checkForOverlaps(): bool
    {
        return self::where('assignment_id', $this->assignment_id)
            ->where('id', '!=', $this->id)
            ->whereIn('status', [ShiftStatus::SCHEDULED, ShiftStatus::IN_PROGRESS])
            ->where(function ($query) {
                $query->whereBetween('start_time', [$this->start_time, $this->end_time])
                    ->orWhereBetween('end_time', [$this->start_time, $this->end_time])
                    ->orWhere(function ($q) {
                        $q->where('start_time', '<=', $this->start_time)
                            ->where('end_time', '>=', $this->end_time);
                    });
            })
            ->exists();
    }

    public function isWithinAssignmentDates(): bool
    {
        if (!$this->assignment) {
            return false;
        }

        $shiftDate = $this->shift_date;
        $assignment = $this->assignment;

        if ($shiftDate < $assignment->start_date) {
            return false;
        }

        if ($assignment->end_date && $shiftDate > $assignment->end_date) {
            return false;
        }

        return true;
    }

    public function isWithinEmployeeAvailability(): bool
    {
        if (!$this->assignment || !$this->assignment->agencyEmployee) {
            return false;
        }

        $employee = $this->assignment->agencyEmployee->employee;
        $shiftDate = $this->shift_date;
        $startTime = $this->start_time;
        $endTime = $this->end_time;
        $dayOfWeek = strtolower($shiftDate->format('l'));

        if ($this->hasTimeOffConflict($employee->id, $shiftDate)) {
            return false;
        }

        if (!$this->checkAvailabilitySettings($employee->id, $shiftDate, $startTime, $endTime, $dayOfWeek)) {
            return false;
        }

        if ($this->hasSchedulingConflict($employee->id, $startTime, $endTime)) {
            return false;
        }

        return true;
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', ShiftStatus::SCHEDULED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', ShiftStatus::IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', ShiftStatus::COMPLETED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', ShiftStatus::CANCELLED);
    }

    public function scopeNoShow($query)
    {
        return $query->where('status', ShiftStatus::NO_SHOW);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [ShiftStatus::SCHEDULED, ShiftStatus::IN_PROGRESS]);
    }

    public function scopeForAssignment($query, $assignmentId)
    {
        return $query->where('assignment_id', $assignmentId);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->whereHas('assignment.agencyEmployee', function ($q) use ($employeeId) {
            $q->where('employee_id', $employeeId);
        });
    }

    public function scopeForAgency($query, $agencyId)
    {
        return $query->whereHas('assignment.contract.agency', function ($q) use ($agencyId) {
            $q->where('id', $agencyId);
        });
    }

    public function scopeForEmployer($query, $employerId)
    {
        return $query->whereHas('assignment.contract', function ($q) use ($employerId) {
            $q->where('employer_id', $employerId);
        });
    }

    public function scopeForLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeDateRange($query, $startDate, $endDate = null)
    {
        return $query->whereBetween('shift_date', [
            $startDate,
            $endDate ?? $startDate
        ]);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_time', '>=', now())
            ->where('status', ShiftStatus::SCHEDULED);
    }

    public function scopePast($query)
    {
        return $query->where('end_time', '<', now());
    }

    public function scopeCurrent($query)
    {
        return $query->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->where('status', ShiftStatus::IN_PROGRESS);
    }

    public function scopeOverlapping($query, $startTime, $endTime, $excludeId = null)
    {
        $query->where(function ($q) use ($startTime, $endTime) {
            $q->whereBetween('start_time', [$startTime, $endTime])
                ->orWhereBetween('end_time', [$startTime, $endTime])
                ->orWhere(function ($q2) use ($startTime, $endTime) {
                    $q2->where('start_time', '<=', $startTime)
                        ->where('end_time', '>=', $endTime);
                });
        })->whereIn('status', [ShiftStatus::SCHEDULED, ShiftStatus::IN_PROGRESS]);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query;
    }

    public function scopeVisibleToAgency(Builder $query, int $agencyId): Builder
    {
        return $query->whereHas('assignment.contract.agency', function (Builder $q) use ($agencyId) {
            $q->where('id', $agencyId);
        });
    }

    public function scopeVisibleToAgent(Builder $query, int $agentId): Builder
    {
        $agent = Agent::find($agentId);
        return $this->scopeVisibleToAgency($query, $agent->agency_id);
    }

    private function hasTimeOffConflict(int $employeeId, Carbon $shiftDate): bool
    {
        return TimeOffRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $shiftDate)
            ->whereDate('end_date', '>=', $shiftDate)
            ->exists();
    }

    private function checkAvailabilitySettings(
        int $employeeId,
        Carbon $shiftDate,
        Carbon $startTime,
        Carbon $endTime,
        string $dayOfWeek
    ): bool {

        $availabilities = EmployeeAvailability::where('employee_id', $employeeId)
            ->where(function ($query) use ($shiftDate) {
                $query->where('start_date', '<=', $shiftDate)
                    ->where(function ($q) use ($shiftDate) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', $shiftDate);
                    });
            })
            ->where('status', 'active')
            ->get();

        if ($availabilities->isEmpty()) {
            return true;
        }

        foreach ($availabilities as $availability) {
            if ($availability->type === 'unavailable') {
                if ($this->isWithinUnavailableBlock($availability, $shiftDate, $startTime, $endTime)) {
                    return false;
                }
                continue;
            }

            if ($this->isWithinAvailableBlock($availability, $dayOfWeek, $startTime, $endTime)) {
                return true;
            }
        }

        return $availabilities->where('type', 'unavailable')->isEmpty();
    }

    private function isWithinUnavailableBlock(
        $availability,
        Carbon $shiftDate,
        Carbon $startTime,
        Carbon $endTime
    ): bool {
        if (
            $availability->start_date > $shiftDate ||
            ($availability->end_date && $availability->end_date < $shiftDate)
        ) {
            return false;
        }

        if (!$availability->start_time && !$availability->end_time) {
            return true;
        }

        $unavailableStart = Carbon::parse($availability->start_time);
        $unavailableEnd = Carbon::parse($availability->end_time);

        return $startTime->lt($unavailableEnd) && $endTime->gt($unavailableStart);
    }

    private function isWithinAvailableBlock($availability, string $dayOfWeek, Carbon $startTime, Carbon $endTime): bool
    {
        $dayBitmask = $this->getDayBitmask($dayOfWeek);

        if (!($availability->days_mask & $dayBitmask)) {
            return false;
        }

        $availableStart = Carbon::parse($availability->start_time);
        $availableEnd = Carbon::parse($availability->end_time);

        return $startTime->gte($availableStart) && $endTime->lte($availableEnd);
    }

    private function getDayBitmask(string $dayOfWeek): int
    {
        $dayMap = [
            'sunday' => 1,
            'monday' => 2,
            'tuesday' => 4,
            'wednesday' => 8,
            'thursday' => 16,
            'friday' => 32,
            'saturday' => 64,
        ];

        return $dayMap[strtolower($dayOfWeek)] ?? 0;
    }

    private function hasSchedulingConflict(int $employeeId, Carbon $startTime, Carbon $endTime): bool
    {
        return Shift::whereHas('assignment.agencyEmployee', function ($query) use ($employeeId) {
            $query->where('employee_id', $employeeId);
        })
            ->where('id', '!=', $this->id)
            ->whereIn('status', [ShiftStatus::SCHEDULED, ShiftStatus::IN_PROGRESS])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            })
            ->exists();
    }
}
