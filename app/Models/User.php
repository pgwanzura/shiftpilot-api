<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\MorphOne;

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
        // Removed address fields as they belong to profileable entities
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
        // Removed latitude and longitude as they belong to profileable entities
    ];

    protected $appends = [
        // Removed full_address, has_complete_address, as they belong to profileable entities
        'has_complete_profile',
        'display_role'
    ];

    /**
     * Role checking methods
     */
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

    /**
     * Get agency ID for agency users
     */
    public function getAgencyId(): ?int
    {
        if ($this->profile && method_exists($this->profile->profileable, 'getAgencyId')) {
            return $this->profile->profileable->getAgencyId();
        }

        return null;
    }

    /**
     * Get employer ID for employer users
     */
    public function getEmployerId(): ?int
    {
        if ($this->profile && method_exists($this->profile->profileable, 'getEmployerId')) {
            return $this->profile->profileable->getEmployerId();
        }

        return null;
    }

    /**
     * Check if user can approve assignments (employer contacts)
     */
    public function canApproveAssignments(): bool
    {
        if ($this->profile && method_exists($this->profile->profileable, 'canApproveAssignments')) {
            return $this->profile->profileable->canApproveAssignments();
        }

        return false;
    }

    /**
     * Check if user can approve timesheets (employer contacts)
     */
    public function canApproveTimesheets(): bool
    {
        if ($this->profile && method_exists($this->profile->profileable, 'canApproveTimesheets')) {
            return $this->profile->profileable->canApproveTimesheets();
        }

        return in_array($this->role, ['employer_admin', 'contact', 'super_admin']);
    }

    /**
     * Check if user can manage agency employees
     */
    public function canManageAgencyEmployees(): bool
    {
        return $this->isAgencyAdmin() || $this->isAgent();
    }

    /**
     * Check if user can manage employer locations
     */
    public function canManageLocations(): bool
    {
        return $this->isEmployerAdmin();
    }

    /**
     * Check if user can create shift requests
     */
    public function canCreateShiftRequests(): bool
    {
        return $this->isEmployerAdmin() || $this->isContact();
    }

    /**
     * Check if user can view financial information
     */
    public function canViewFinancials(): bool
    {
        return $this->isAdmin() || $this->isAgencyAdmin();
    }

    /**
     * Get user's primary entity (Agency, Employer, etc.)
     */
    public function getPrimaryEntity()
    {
        return $this->profile?->profileable;
    }

    /**
     * Get user's display role with entity name
     */
    public function getDisplayRoleAttribute(): string
    {
        $entity = $this->getPrimaryEntity();
        $entityName = $entity?->name ?? '';

        return match ($this->role) {
            'super_admin' => 'Super Administrator',
            'agency_admin' => "Agency Admin" . ($entityName ? " - {$entityName}" : ''),
            'agent' => "Agent" . ($entityName ? " - {$entityName}" : ''),
            'employer_admin' => "Employer Admin" . ($entityName ? " - {$entityName}" : ''),
            'contact' => "Contact" . ($entityName ? " - {$entityName}" : ''),
            'employee' => "Employee",
            default => ucfirst(str_replace('_', ' ', $this->role))
        };
    }

    /**
     * Check if user has complete profile
     */
    public function getHasCompleteProfileAttribute(): bool
    {
        // A user profile is complete if the base user details are set and a profileable entity exists
        $required = ['name', 'phone'];

        foreach ($required as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }

        return (bool) $this->profile;
    }

    /**
     * Relationships
     */
    public function profile(): MorphOne
    {
        return $this->morphOne(Profile::class, 'profileable');
    }

    /**
     * Check if user can manage shifts (existing method)
     */
    public function canManageShifts(): bool
    {
        return in_array($this->role, ['agency_admin', 'employer_admin', 'agent', 'super_admin']);
    }
}
