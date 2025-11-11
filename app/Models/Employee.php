<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agencyEmployees()
    {
        return $this->hasMany(AgencyEmployee::class);
    }

    public function agencies()
    {
        return $this->belongsToMany(Agency::class, 'agency_employees')
                    ->using(AgencyEmployee::class)
                    ->withPivot('position', 'pay_rate', 'employment_type', 'status', 'contract_start_date', 'contract_end_date')
                    ->withTimestamps();
    }

    public function timesheets()
    {
        return $this->hasMany(Timesheet::class);
    }

    public function employeeAvailabilities()
    {
        return $this->hasMany(EmployeeAvailability::class);
    }

    public function timeOffRequests()
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
}