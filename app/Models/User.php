<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'status',
        'address',
        'date_of_birth',
        'emergency_contact_name',
        'emergency_contact_phone',
        'meta',
        'email_verified_at',
        'last_login_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'date_of_birth' => 'date',
        'meta' => 'array'
    ];

    public function getHasCompleteProfileAttribute()
    {
        $required = ['name', 'phone', 'date_of_birth'];

        foreach ($required as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }

        if ($this->isEmployee() && empty($this->emergency_contact_phone)) {
            return false;
        }

        return true;
    }

    public function agency()
    {
        return $this->hasOne(Agency::class);
    }

    public function agent()
    {
        return $this->hasOne(Agent::class);
    }

    public function employer()
    {
        return $this->hasOne(Employer::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function contact()
    {
        return $this->hasOne(Contact::class);
    }

    public function isSuperAdmin()
    {
        return $this->role === 'super_admin';
    }

    public function isAgencyAdmin()
    {
        return $this->role === 'agency_admin';
    }

    public function isEmployerAdmin()
    {
        return $this->role === 'employer_admin';
    }

    public function isEmployee()
    {
        return $this->role === 'employee';
    }

    public function isContact()
    {
        return $this->role === 'contact';
    }

    /**
     * Permission checks
     */
    public function canApproveTimesheets()
    {
        return in_array($this->role, ['employer_admin', 'contact', 'super_admin']);
    }

    public function canManageShifts()
    {
        return in_array($this->role, ['agency_admin', 'employer_admin', 'agent', 'super_admin']);
    }

    public function isAgency()
    {
        return $this->role === 'agency_admin' || $this->agency !== null;
    }

    public function isEmployer()
    {
        return $this->role === 'employer_admin' || $this->employer !== null;
    }

    public function isAgent()
    {
        return $this->role === 'agent' || $this->agent !== null;
    }
}
