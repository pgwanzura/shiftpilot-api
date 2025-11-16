<?php

namespace App\Services;

use App\Models\User;

class UserRoleService
{
    public function getContextualId(User $user): ?int
    {
        return match ($user->role) {
            'agency_admin' => $user->agency?->id,
            'agent' => $user->agent?->agency_id,
            'employer_admin' => $user->employerUser?->employer_id,
            'contact' => $user->contact?->employer_id,
            'employee' => $user->employee?->agencyEmployees()->first()?->agency_id,
            default => null
        };
    }

    public function can(User $user, string $permission): bool
    {
        $permissionService = app(PermissionService::class);
        return $permissionService->check($user, $permission);
    }

    public function getDisplayRole(User $user): string
    {
        $entity = $this->getPrimaryEntity($user);
        $entityName = $entity?->name ?? '';

        return match ($user->role) {
            'super_admin' => 'Super Administrator',
            'agency_admin' => $entityName ? "Agency Administrator - {$entityName}" : 'Agency Administrator',
            'agent' => $entityName ? "Agency Agent - {$entityName}" : 'Agency Agent',
            'employer_admin' => $entityName ? "Employer Administrator - {$entityName}" : 'Employer Administrator',
            'contact' => $entityName ? "Employer Contact - {$entityName}" : 'Employer Contact',
            'employee' => 'Employee',
            default => ucfirst(str_replace('_', ' ', $user->role))
        };
    }

    private function getPrimaryEntity(User $user): mixed
    {
        return match ($user->role) {
            'agency_admin' => $user->agency,
            'agent' => $user->agent?->agency,
            'employer_admin' => $user->employerUser?->employer,
            'contact' => $user->contact?->employer,
            'employee' => $user->employee,
            default => null
        };
    }
}
