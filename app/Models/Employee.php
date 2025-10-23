<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'agency_id',
        'employer_id',
        'position',
        'pay_rate',
        'availability',
        'qualifications',
        'employment_type',
        'status',
        'meta',
    ];

    protected $casts = [
        'pay_rate' => 'decimal:2',
        'availability' => 'array',
        'qualifications' => 'array',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function placements()
    {
        return $this->hasMany(Placement::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function timesheets()
    {
        return $this->hasMany(Timesheet::class);
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public function employeeAvailabilities()
    {
        return $this->hasMany(EmployeeAvailability::class);
    }

    public function timeOffRequests()
    {
        return $this->hasMany(TimeOffRequest::class);
    }

    public function shiftOffers()
    {
        return $this->hasMany(ShiftOffer::class);
    }
}
