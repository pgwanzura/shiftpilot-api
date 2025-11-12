<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Employee extends Model
{
    use HasFactory;

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'national_insurance_number',
        'date_of_birth',
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
    ];

    /**
     * Get the employee's profile.
     */
    public function profile(): MorphOne
    {
        return $this->morphOne(Profile::class, 'profileable');
    }

    public function agencyEmployees(): HasMany
    {
        return $this->hasMany(AgencyEmployee::class);
    }

    public function agencies(): BelongsToMany
    {
        return $this->belongsToMany(Agency::class, 'agency_employees')
                    ->using(AgencyEmployee::class)
                    ->withPivot('position', 'pay_rate', 'employment_type', 'status', 'contract_start_date', 'contract_end_date')
                    ->withTimestamps();
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    public function payrolls(): HasManyThrough
    {
        return $this->hasManyThrough(
            Payroll::class,
            AgencyEmployee::class,
            'employee_id', // Foreign key on agency_employees table
            'agency_employee_id', // Foreign key on payrolls table
            'id', // Local key on employees table
            'id' // Local key on agency_employees table
        );
    }

    public function shiftOffers(): HasManyThrough
    {
        return $this->hasManyThrough(
            ShiftOffer::class,
            AgencyEmployee::class,
            'employee_id', // Foreign key on agency_employees table
            'agency_employee_id', // Foreign key on shift_offers table
            'id', // Local key on employees table
            'id' // Local key on agency_employees table
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

    public function getAssignments()
    {
        return Assignment::whereIn('agency_employee_id',
            $this->agencyEmployees()->pluck('id')
        )->get();
    }

    public function getActiveAssignments()
    {
        return Assignment::whereIn('agency_employee_id',
            $this->agencyEmployees()->pluck('id')
        )->where('status', 'active')->get();
    }

    public function assignmentsForAgency($agencyId)
    {
        $agencyEmployeeIds = $this->agencyEmployees()
            ->where('agency_id', $agencyId)
            ->pluck('id');

        return Assignment::whereIn('agency_employee_id', $agencyEmployeeIds)->get();
    }

    public function getShifts()
    {
        return Shift::whereIn('assignment_id',
            $this->getAssignments()->pluck('id')
        )->get();
    }

    public function isRegisteredWithAgency($agencyId)
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

    public function hasRequiredQualifications($requiredQualifications)
    {
        if (empty($requiredQualifications)) {
            return true;
        }

        $employeeQualifications = $this->qualifications ?? [];
        return !empty(array_intersect($requiredQualifications, $employeeQualifications));
    }

    public function getAgeAttribute()
    {
        return $this->date_of_birth?->age;
    }

    public function isAvailableForShift($shiftStart, $shiftEnd)
    {
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

        $hasOverlappingShift = $this->getShifts()
            ->where(function ($query) use ($shiftStart, $shiftEnd) {
                $query->whereBetween('start_time', [$shiftStart, $shiftEnd])
                      ->orWhereBetween('end_time', [$shiftStart, $shiftEnd])
                      ->orWhere(function ($q) use ($shiftStart, $shiftEnd) {
                          $q->where('start_time', '<=', $shiftStart)
                            ->where('end_time', '>=', $shiftEnd);
                      });
            })
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->exists();

        return !$hasOverlappingShift;
    }

    /**
     * Employee cannot approve assignments directly.
     */
    public function canApproveAssignments(): bool
    {
        return false;
    }

    /**
     * Employee cannot approve timesheets directly.
     */
    public function canApproveTimesheets(): bool
    {
        return false;
    }
}