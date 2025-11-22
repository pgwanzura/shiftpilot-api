<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'meta',
    ];

    protected $guarded = [
        'role',
        'status',
        'email_verified_at',
        'last_login_at',
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

    public function agent(): HasOne
    {
        return $this->hasOne(Agent::class);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function contact(): HasOne
    {
        return $this->hasOne(Contact::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAgencyAdmin(): bool
    {
        return $this->role === 'agency_admin';
    }

    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    public function isEmployerAdmin(): bool
    {
        return $this->role === 'employer_admin';
    }

    public function isContact(): bool
    {
        return $this->role === 'contact';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    public function isAgency(): bool
    {
        return $this->isAgencyAdmin() || $this->isAgent();
    }

    public function isEmployer(): bool
    {
        return $this->isEmployerAdmin() || $this->isContact();
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getAgencyId(): ?int
    {
        if ($this->isAgencyAdmin() || $this->isAgent()) {
            if ($this->relationLoaded('agent')) {
                return $this->agent?->agency_id;
            }
            return $this->agent()->value('agency_id');
        }

        return null;
    }

    public function getEmployerId(): ?int
    {
        if ($this->isEmployerAdmin()) {
            if ($this->relationLoaded('employee')) {
                return $this->employee?->employer_id;
            }
            return $this->employee()->value('employer_id');
        }

        if ($this->isContact()) {
            if ($this->relationLoaded('contact')) {
                return $this->contact?->employer_id;
            }
            return $this->contact()->value('employer_id');
        }

        return null;
    }

    public function getOrganizationId(): ?int
    {
        return match ($this->role) {
            'agency_admin', 'agent' => $this->getAgencyId(),
            'employer_admin', 'contact' => $this->getEmployerId(),
            'employee' => $this->employee?->id,
            default => null,
        };
    }

    public function recordLogin(): void
    {
        $this->forceFill(['last_login_at' => now()])->save();
    }

    public function markAsVerified(): void
    {
        $this->forceFill(['email_verified_at' => now()])->save();
    }

    public function activate(): void
    {
        $this->forceFill(['status' => 'active'])->save();
    }

    public function deactivate(): void
    {
        $this->forceFill(['status' => 'inactive'])->save();
    }

    public function hasCompleteProfile(): bool
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
            'agency_admin' => $this->agency_id !== null,
            'employer_admin' => $this->relationLoaded('employee') &&
                $this->employee !== null &&
                $this->employee->employer_id !== null,
            'employee' => $this->relationLoaded('employee') && $this->employee !== null,
            'contact' => $this->relationLoaded('contact') &&
                $this->contact !== null &&
                $this->contact->employer_id !== null,
            'agent' => $this->relationLoaded('agent') &&
                $this->agent !== null &&
                $this->agent->agency_id !== null,
            default => true,
        };
    }

    public function hasPermission(string $permission): bool
    {
        $rolePermissions = $this->getCachedPermissions();

        if (in_array('*', $rolePermissions, true)) {
            return true;
        }

        foreach ($rolePermissions as $rolePermission) {
            if ($rolePermission === $permission) {
                return true;
            }

            if ($this->matchesWildcardPermission($rolePermission, $permission)) {
                return true;
            }
        }

        return false;
    }

    public function getDisplayRole(): string
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

    public static function getAgencyRoles(): array
    {
        return ['agency_admin', 'agent'];
    }

    public static function getEmployerRoles(): array
    {
        return ['employer_admin', 'contact'];
    }

    public function clearPermissionsCache(): void
    {
        Cache::forget($this->getPermissionsCacheKey());
    }

    protected function getCachedPermissions(): array
    {
        return Cache::remember(
            $this->getPermissionsCacheKey(),
            3600,
            fn() => $this->getRolePermissions()
        );
    }

    protected function getPermissionsCacheKey(): string
    {
        return "user.{$this->id}.permissions.{$this->role}";
    }

    protected function matchesWildcardPermission(string $rolePermission, string $requiredPermission): bool
    {
        if (!str_contains($rolePermission, ':')) {
            return false;
        }

        [$resource, $actions] = explode(':', $rolePermission, 2);

        if (!str_contains($requiredPermission, ':')) {
            return false;
        }

        [$requiredResource, $requiredAction] = explode(':', $requiredPermission, 2);

        if ($resource !== $requiredResource) {
            return false;
        }

        $actionList = explode(',', $actions);

        return in_array('*', $actionList, true) || in_array($requiredAction, $actionList, true);
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

    protected static function booted(): void
    {
        static::updated(function (User $user) {
            if ($user->wasChanged('role')) {
                $user->clearPermissionsCache();
            }
        });
    }
}
