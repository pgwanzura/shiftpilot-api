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

    public function agent()
    {
        return $this->hasOne(Agent::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function contact()
    {
        return $this->hasOne(Contact::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function recordLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    public function markAsVerified(): void
    {
        $this->update(['email_verified_at' => now()]);
    }

    public function getHasCompleteProfileAttribute(): bool
    {
        $requiredFields = ['name', 'email', 'phone'];

        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }

        if (!$this->hasVerifiedEmail()) {
            return false;
        }

        return match ($this->role) {
            'agency_admin' => $this->agency !== null,
            'employer_admin' => $this->employee !== null && $this->employee->employer !== null,
            'employee' => $this->employee !== null,
            'contact' => $this->contact !== null && $this->contact->employer !== null,
            'agent' => $this->agent !== null && $this->agent->agency !== null,
            default => true,
        };
    }

    public function canApproveTimesheets(): bool
    {
        return $this->hasPermission('timesheet:approve');
    }

    public function canManageShifts(): bool
    {
        return $this->hasPermission('shift:manage');
    }

    public function canViewReports(): bool
    {
        return $this->hasPermission('revenue:view') ||
            $this->hasPermission('analytics:view') ||
            $this->hasPermission('performance:view');
    }

    public function canManageContracts(): bool
    {
        return $this->hasPermission('contract:manage');
    }

    public function canManageEmployees(): bool
    {
        return $this->hasPermission('employee:manage');
    }

    public function canManageAgents(): bool
    {
        return $this->hasPermission('agent:manage');
    }

    public function canManageShiftRequests(): bool
    {
        return $this->hasPermission('shift_request:manage');
    }

    public function canManageAssignments(): bool
    {
        return $this->hasPermission('assignment:manage');
    }

    public function canManageAvailability(): bool
    {
        return $this->hasPermission('availability:manage');
    }

    public function canApproveTimeOff(): bool
    {
        return $this->hasPermission('time_off:approve');
    }

    public function canManageInvoices(): bool
    {
        return $this->hasPermission('invoice:manage');
    }

    public function canProcessPayroll(): bool
    {
        return $this->hasPermission('payroll:process');
    }

    public function canManageAgencySettings(): bool
    {
        return $this->hasPermission('agency:configure');
    }

    public function canManageBilling(): bool
    {
        return $this->hasPermission('billing:manage');
    }

    public function canManageContacts(): bool
    {
        return $this->hasPermission('contact:manage');
    }

    public function canManageLocations(): bool
    {
        return $this->hasPermission('location:manage');
    }

    public function canViewStaff(): bool
    {
        return $this->hasPermission('staff:view');
    }

    public function canCreateShiftRequests(): bool
    {
        return $this->hasPermission('shift_request:create');
    }

    public function canManageOwnProfile(): bool
    {
        return $this->hasPermission('profile:manage');
    }

    public function canManageOwnAvailability(): bool
    {
        return $this->hasPermission('availability:manage:own');
    }

    public function canViewOwnTimesheets(): bool
    {
        return $this->hasPermission('timesheet:view:own');
    }

    public function canCreateOwnTimesheets(): bool
    {
        return $this->hasPermission('timesheet:create:own');
    }

    public function canViewOwnAssignments(): bool
    {
        return $this->hasPermission('assignment:view:own');
    }

    public function canRequestTimeOff(): bool
    {
        return $this->hasPermission('time_off:request');
    }

    public function canViewOwnShifts(): bool
    {
        return $this->hasPermission('shift:view:own');
    }

    public function canRespondToShiftOffers(): bool
    {
        return $this->hasPermission('shift_offer:respond:own');
    }

    public function isAgencyAdmin(): bool
    {
        return $this->role === 'agency_admin';
    }

    public function isEmployerAdmin(): bool
    {
        return $this->role === 'employer_admin';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    public function isContact(): bool
    {
        return $this->role === 'contact';
    }

    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    public function getDisplayRoleAttribute(): string
    {
        return match ($this->role) {
            'super_admin' => 'Super Administrator',
            'agency_admin' => 'Agency Administrator',
            'employer_admin' => 'Employer Administrator',
            'employee' => 'Employee',
            'contact' => 'Contact',
            'agent' => 'Agent',
            default => 'User',
        };
    }

    public function getContextualIdAttribute()
    {
        return match ($this->role) {
            'agency_admin' => $this->agency?->id,
            'employer_admin' => $this->employee?->employer?->id,
            'employee' => $this->employee?->id,
            'contact' => $this->contact?->employer?->id,
            'agent' => $this->agent?->agency?->id,
            default => null,
        };
    }

    protected function hasPermission(string $permission): bool
    {
        $rolePermissions = $this->getRolePermissions();

        if (in_array('*', $rolePermissions)) {
            return true;
        }

        foreach ($rolePermissions as $rolePermission) {
            if ($rolePermission === $permission) {
                return true;
            }

            if (str_contains($rolePermission, ':')) {
                [$resource, $actions] = explode(':', $rolePermission);
                [$requiredResource, $requiredAction] = explode(':', $permission);

                if ($resource === $requiredResource) {
                    $actionList = explode(',', $actions);
                    if (in_array('*', $actionList) || in_array($requiredAction, $actionList)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    protected function getRolePermissions(): array
    {
        return match ($this->role) {
            'super_admin' => ['*'],
            'agency_admin' => [
                'employee:*',
                'agent:*',
                'shift_request:*',
                'shift:*',
                'assignment:*',
                'timesheet:*',
                'invoice:view,create',
                'payroll:create,view',
                'availability:view,manage',
                'time_off:approve',
                'agency:configure',
                'billing:manage',
                'revenue:view',
                'performance:view',
            ],
            'agent' => [
                'employee:view,manage',
                'shift_request:create,view',
                'shift:create,view,update',
                'assignment:view,create,update',
                'timesheet:view,approve',
                'invoice:view,create',
                'availability:view,manage',
                'time_off:approve',
                'performance:view',
            ],
            'employer_admin' => [
                'shift_request:*',
                'shift:create,view,update',
                'assignment:view',
                'contact:manage',
                'timesheet:approve,view',
                'invoice:view,create,pay',
                'location:manage',
                'analytics:view',
            ],
            'contact' => [
                'timesheet:approve',
                'shift:view',
                'staff:view',
            ],
            'employee' => [
                'shift:view:own',
                'assignment:view:own',
                'timesheet:create:own,view:own',
                'availability:manage:own',
                'time_off:request',
                'profile:manage',
                'shift_offer:respond:own',
            ],
            default => [],
        };
    }
}
