<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasFactory;

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'user_id',
        'national_insurance_number',
        'date_of_birth',
        'address_line1',
        'address_line2',
        'city',
        'county',
        'postcode',
        'country',
        'latitude',
        'longitude',
        'emergency_contact_name',
        'emergency_contact_phone',
        'qualifications',
        'certifications',
        'status',
        'meta',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'qualifications' => 'array',
        'certifications' => 'array',
        'meta' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    protected $appends = [
        'age',
        'is_active',
        'has_active_agencies',
        'full_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function preferences(): HasOne
    {
        return $this->hasOne(EmployeePreference::class);
    }

    public function agencyEmployees(): HasMany
    {
        return $this->hasMany(AgencyEmployee::class);
    }

    public function agencies(): BelongsToMany
    {
        return $this->belongsToMany(Agency::class, 'agency_employees')
            ->withPivot('position', 'pay_rate', 'employment_type', 'status', 'contract_start_date', 'contract_end_date')
            ->withTimestamps();
    }

    public function assignments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Assignment::class,
            AgencyEmployee::class,
            'employee_id',
            'agency_employee_id'
        );
    }

    public function shifts(): HasManyThrough
    {
        return $this->hasManyThrough(
            Shift::class,
            Assignment::class,
            'agency_employee_id',
            'assignment_id',
            'id',
            'id'
        );
    }

    public function timesheets(): HasManyThrough
    {
        return $this->hasManyThrough(
            Timesheet::class,
            Shift::class,
            'assignment_id',
            'shift_id',
            'id',
            'id'
        );
    }

    public function payrolls(): HasManyThrough
    {
        return $this->hasManyThrough(
            Payroll::class,
            AgencyEmployee::class,
            'employee_id',
            'agency_employee_id'
        );
    }

    public function shiftOffers(): HasManyThrough
    {
        return $this->hasManyThrough(
            ShiftOffer::class,
            AgencyEmployee::class,
            'employee_id',
            'agency_employee_id'
        );
    }

    public function employeeAvailabilities(): HasMany
    {
        return $this->hasMany(EmployeeAvailability::class);
    }

    public function timeOffRequests(): HasMany
    {
        return $this->hasMany(TimeOffRequest::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', self::STATUS_SUSPENDED);
    }

    public function scopeWithActiveAgencies($query)
    {
        return $query->whereHas('agencyEmployees', function ($q) {
            $q->where('status', 'active');
        });
    }

    public function scopeWithQualifications($query, array $qualifications)
    {
        return $query->where(function ($q) use ($qualifications) {
            foreach ($qualifications as $qualification) {
                $q->orWhereJsonContains('qualifications', $qualification);
            }
        });
    }

    public function scopeAvailableForShift($query, $startTime, $endTime)
    {
        return $query->whereDoesntHave('timeOffRequests', function ($q) use ($startTime, $endTime) {
            $q->where('status', 'approved')
                ->where(function ($subQ) use ($startTime, $endTime) {
                    $subQ->whereBetween('start_date', [$startTime, $endTime])
                        ->orWhereBetween('end_date', [$startTime, $endTime])
                        ->orWhere(function ($innerQ) use ($startTime, $endTime) {
                            $innerQ->where('start_date', '<=', $startTime)
                                ->where('end_date', '>=', $endTime);
                        });
                });
        })->whereDoesntHave('shifts', function ($q) use ($startTime, $endTime) {
            $q->whereNotIn('status', ['cancelled', 'no_show'])
                ->where(function ($subQ) use ($startTime, $endTime) {
                    $subQ->whereBetween('start_time', [$startTime, $endTime])
                        ->orWhereBetween('end_time', [$startTime, $endTime])
                        ->orWhere(function ($innerQ) use ($startTime, $endTime) {
                            $innerQ->where('start_time', '<=', $startTime)
                                ->where('end_time', '>=', $endTime);
                        });
                });
        });
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getHasActiveAgenciesAttribute(): bool
    {
        return $this->agencyEmployees()->where('status', 'active')->exists();
    }

    public function getFullAddressAttribute(): string
    {
        $addressParts = array_filter([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->county,
            $this->postcode,
            $this->country,
        ]);

        return implode(', ', $addressParts);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function canWork(): bool
    {
        return $this->isActive() && $this->has_active_agencies;
    }

    public function isRegisteredWithAgency($agencyId): bool
    {
        return $this->agencyEmployees()
            ->where('agency_id', $agencyId)
            ->where('status', 'active')
            ->exists();
    }

    public function getActiveAgencies()
    {
        return $this->agencies()->wherePivot('status', 'active')->get();
    }

    public function hasRequiredQualifications(array $requiredQualifications): bool
    {
        if (empty($requiredQualifications)) {
            return true;
        }

        $employeeQualifications = $this->qualifications ?? [];
        return !empty(array_intersect($requiredQualifications, $employeeQualifications));
    }

    public function hasRequiredCertifications(array $requiredCertifications): bool
    {
        if (empty($requiredCertifications)) {
            return true;
        }

        $employeeCertifications = $this->certifications ?? [];
        return !empty(array_intersect($requiredCertifications, $employeeCertifications));
    }

    public function isAvailableForShift($shiftStart, $shiftEnd): bool
    {
        if (!$this->canWork()) {
            return false;
        }

        $hasTimeOff = $this->timeOffRequests()
            ->where('status', 'approved')
            ->where(function ($query) use ($shiftStart, $shiftEnd) {
                $query->whereBetween('start_date', [$shiftStart, $shiftEnd])
                    ->orWhereBetween('end_date', [$shiftStart, $shiftEnd])
                    ->orWhere(function ($q) use ($shiftStart, $shiftEnd) {
                        $q->where('start_date', '<=', $shiftStart)
                            ->where('end_date', '>=', $shiftEnd);
                    });
            })
            ->exists();

        if ($hasTimeOff) {
            return false;
        }

        $hasOverlappingShift = $this->shifts()
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->where(function ($query) use ($shiftStart, $shiftEnd) {
                $query->whereBetween('start_time', [$shiftStart, $shiftEnd])
                    ->orWhereBetween('end_time', [$shiftStart, $shiftEnd])
                    ->orWhere(function ($q) use ($shiftStart, $shiftEnd) {
                        $q->where('start_time', '<=', $shiftStart)
                            ->where('end_time', '>=', $shiftEnd);
                    });
            })
            ->exists();

        return !$hasOverlappingShift;
    }

    public function meetsPreferences($shiftData): bool
    {
        if (!$this->preferences) {
            return true;
        }

        $preferences = $this->preferences;

        if ($preferences->min_hourly_rate && $shiftData['hourly_rate'] < $preferences->min_hourly_rate) {
            return false;
        }

        if ($preferences->preferred_locations && !in_array($shiftData['location_id'], $preferences->preferred_locations)) {
            return false;
        }

        if ($preferences->preferred_roles && !in_array($shiftData['role'], $preferences->preferred_roles)) {
            return false;
        }

        return true;
    }

    public function getCurrentAssignments()
    {
        return $this->assignments()
            ->whereIn('status', ['active', 'pending'])
            ->get();
    }

    public function getUpcomingShifts()
    {
        return $this->shifts()
            ->where('start_time', '>=', now())
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->orderBy('start_time')
            ->get();
    }

    public function getTotalHoursWorked($startDate = null, $endDate = null)
    {
        $query = $this->timesheets()
            ->where('status', 'approved');

        if ($startDate) {
            $query->where('pay_period_start', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('pay_period_end', '<=', $endDate);
        }

        return $query->sum('hours_worked');
    }

    public function canBeAssigned(): bool
    {
        return $this->isActive() && $this->has_active_agencies;
    }

    public function suspend(): bool
    {
        if ($this->isSuspended()) {
            return false;
        }

        return $this->update(['status' => self::STATUS_SUSPENDED]);
    }

    public function activate(): bool
    {
        if ($this->isActive()) {
            return false;
        }

        return $this->update(['status' => self::STATUS_ACTIVE]);
    }

    public function deactivate(): bool
    {
        if ($this->isInactive()) {
            return false;
        }

        return $this->update(['status' => self::STATUS_INACTIVE]);
    }

    public function hasGeoLocation(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    public function getActiveAgencyEmployees()
    {
        return $this->agencyEmployees()->where('status', 'active')->get();
    }

    public function getPrimaryAgency()
    {
        return $this->agencies()->wherePivot('status', 'active')->first();
    }

    public function hasCompleteProfile(): bool
    {
        $requiredFields = [
            'national_insurance_number',
            'date_of_birth',
            'address_line1',
            'city',
            'postcode',
            'emergency_contact_name',
            'emergency_contact_phone'
        ];

        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }

        return true;
    }
}
