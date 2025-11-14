<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'status',
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
        'meta' => 'array',
    ];

    protected $appends = [
        'has_complete_profile',
        'display_role'
    ];

    public function isAdmin(): bool
    {
        return $this->isSuperAdmin();
    }

    public function isAgency(): bool
    {
        return $this->profile?->profileable instanceof Agency || $this->profile?->profileable instanceof Agent;
    }

    public function isAgencyAdmin(): bool
    {
        return $this->role === 'agency_admin' && $this->profile?->profileable instanceof Agency;
    }

    public function isAgent(): bool
    {
        return $this->role === 'agent' && $this->profile?->profileable instanceof Agent;
    }

    public function isEmployer(): bool
    {
        return $this->profile?->profileable instanceof Employer || $this->profile?->profileable instanceof Contact;
    }

    public function isEmployerAdmin(): bool
    {
        return $this->role === 'employer_admin' && $this->profile?->profileable instanceof Employer;
    }

    public function isContact(): bool
    {
        return $this->role === 'contact' && $this->profile?->profileable instanceof Contact;
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee' && $this->profile?->profileable instanceof Employee;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function getAgencyId(): ?int
    {
        if (!$this->profile) {
            return null;
        }

        $profileable = $this->profile->profileable;

        if ($profileable instanceof Agency) {
            return $profileable->id;
        }

        if ($profileable instanceof Agent && $profileable->agency_id) {
            return $profileable->agency_id;
        }

        if ($profileable instanceof Employee && $profileable->agency_id) {
            return $profileable->agency_id;
        }

        return null;
    }

    public function getEmployerId(): ?int
    {
        if (!$this->profile) {
            return null;
        }

        $profileable = $this->profile->profileable;

        if ($profileable instanceof Employer) {
            return $profileable->id;
        }

        if ($profileable instanceof Contact && $profileable->employer_id) {
            return $profileable->employer_id;
        }

        return null;
    }

    public function canApproveAssignments(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!$this->profile) {
            return false;
        }

        $profileable = $this->profile->profileable;

        if ($profileable instanceof Contact) {
            return $profileable->can_approve_assignments;
        }

        if ($profileable instanceof Employer) {
            return true;
        }

        return false;
    }

    public function canApproveTimesheets(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!$this->profile) {
            return false;
        }

        $profileable = $this->profile->profileable;

        if ($profileable instanceof Contact) {
            return $profileable->can_approve_timesheets;
        }

        return $this->isEmployerAdmin() || $this->isAgencyAdmin();
    }

    public function canManageAgencyEmployees(): bool
    {
        return $this->isAgencyAdmin() || $this->isAgent() || $this->isSuperAdmin();
    }

    public function canManageLocations(): bool
    {
        return $this->isEmployerAdmin() || $this->isSuperAdmin();
    }

    public function canCreateShiftRequests(): bool
    {
        return $this->isEmployerAdmin() || $this->isContact() || $this->isSuperAdmin();
    }

    public function canViewFinancials(): bool
    {
        return $this->isSuperAdmin() || $this->isAgencyAdmin();
    }

    public function canManageShifts(): bool
    {
        return $this->isAgencyAdmin() || $this->isEmployerAdmin() || $this->isAgent() || $this->isSuperAdmin();
    }

    public function canManageContracts(): bool
    {
        return $this->isAgencyAdmin() || $this->isEmployerAdmin() || $this->isSuperAdmin();
    }

    public function canViewReports(): bool
    {
        return $this->isSuperAdmin() || $this->isAgencyAdmin() || $this->isEmployerAdmin();
    }

    public function getPrimaryEntity()
    {
        return $this->profile?->profileable;
    }

    public function getDisplayRoleAttribute(): string
    {
        $entity = $this->getPrimaryEntity();
        $entityName = $entity?->name ?? '';

        return match ($this->role) {
            'super_admin' => 'Super Administrator',
            'agency_admin' => "Agency Administrator" . ($entityName ? " - {$entityName}" : ''),
            'agent' => "Agency Agent" . ($entityName ? " - {$entityName}" : ''),
            'employer_admin' => "Employer Administrator" . ($entityName ? " - {$entityName}" : ''),
            'contact' => "Employer Contact" . ($entityName ? " - {$entityName}" : ''),
            'employee' => "Employee",
            default => ucfirst(str_replace('_', ' ', $this->role))
        };
    }

    public function getHasCompleteProfileAttribute(): bool
    {
        $requiredFields = ['name', 'email', 'phone'];

        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }

        if (!$this->profile) {
            return false;
        }

        $profileable = $this->profile->profileable;

        if (!$profileable) {
            return false;
        }

        if (method_exists($profileable, 'hasCompleteProfile')) {
            return $profileable->hasCompleteProfile();
        }

        return true;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeAgencyUsers($query)
    {
        return $query->whereIn('role', ['agency_admin', 'agent']);
    }

    public function scopeEmployerUsers($query)
    {
        return $query->whereIn('role', ['employer_admin', 'contact']);
    }

    public function markAsVerified()
    {
        $this->update(['email_verified_at' => now()]);
    }

    public function recordLogin()
    {
        $this->update(['last_login_at' => now()]);
    }

    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function canImpersonate(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canBeImpersonated(): bool
    {
        return !$this->isSuperAdmin();
    }
}
