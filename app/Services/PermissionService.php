<?php

namespace App\Services;

use App\Models\User;

class PermissionService
{
    private const PERMISSIONS = [
        'super_admin' => ['*'],
        'agency_admin' => [
            'manage.agency_employees',
            'view.financials',
            'manage.shifts',
            'manage.contracts',
            'view.reports',
            'approve.timesheets',
        ],
        'agent' => [
            'manage.agency_employees',
            'manage.shifts',
            'approve.timesheets',
        ],
        'employer_admin' => [
            'manage.locations',
            'create.shift_requests',
            'manage.shifts',
            'manage.contracts',
            'view.reports',
            'approve.timesheets',
            'approve.assignments',
        ],
        'contact' => [
            'create.shift_requests',
            'approve.timesheets',
            'approve.assignments',
        ],
        'employee' => [
            'view.own_timesheets',
            'view.own_shifts',
        ],
    ];

    public function check(User $user, string $permission): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $userPermissions = self::PERMISSIONS[$user->role] ?? [];

        if (in_array('*', $userPermissions)) {
            return true;
        }

        return in_array($permission, $userPermissions);
    }

    public function getPermissionsForRole(string $role): array
    {
        return self::PERMISSIONS[$role] ?? [];
    }
}
