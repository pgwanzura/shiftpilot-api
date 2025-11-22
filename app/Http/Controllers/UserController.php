<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserRoleService;

use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private UserRoleService $roleService,

        private PermissionService $permissionService
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->loadRoleSpecificRelations($user);

        return response()->json([
            'user' => $user,
            'permissions' => $this->getUserPermissions($user),

            'contextual_id' => $this->roleService->getContextualId($user),
            'display_role' => $this->roleService->getDisplayRole($user),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'meta' => ['sometimes', 'array'],
        ]);

        $user->update($validated);
        $this->loadRoleSpecificRelations($user);

        return response()->json([
            'user' => $user,

            'message' => 'Profile updated successfully'
        ]);
    }

    private function loadRoleSpecificRelations(User $user): void
    {
        $relations = match ($user->role) {
            'agency_admin' => ['agency'],
            'agent' => ['agent.agency'],
            'employer_admin' => ['employerUser.employer'],
            'contact' => ['contact.employer'],
            'employee' => ['employee.agencyEmployees.agency'],
            default => []
        };

        if (!empty($relations)) {
            $user->load($relations);
        }
    }

    private function getUserPermissions(User $user): array
    {
        $permissions = [
            'approve.timesheets',
            'manage.shifts',
            'manage.agency_employees',
            'manage.locations',
            'create.shift_requests',
            'view.financials',
            'manage.contracts',
            'view.reports',
            'approve.assignments',
        ];

        $userPermissions = [];
        foreach ($permissions as $permission) {
            $userPermissions[$permission] = $this->roleService->can($user, $permission);
        }

        return array_merge($userPermissions, [
            'is_super_admin' => $user->isSuperAdmin(),
            'is_agency_admin' => $user->isAgencyAdmin(),
            'is_agent' => $user->isAgent(),
            'is_employer_admin' => $user->isEmployerAdmin(),
            'is_employee' => $user->isEmployee(),
            'is_contact' => $user->isContact(),
        ]);
    }
}
