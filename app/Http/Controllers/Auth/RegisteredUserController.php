<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PermissionService;
use App\Services\UserRoleService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    public function __construct(
        private PermissionService $permissionService,
        private UserRoleService $userRoleService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', 'in:employee,employer_admin,agency_admin,contact,agent'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'county' => ['nullable', 'string', 'max:100'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'size:2'],
            'date_of_birth' => ['nullable', 'date'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'address_line1' => $validated['address_line1'] ?? null,
            'address_line2' => $validated['address_line2'] ?? null,
            'city' => $validated['city'] ?? null,
            'county' => $validated['county'] ?? null,
            'postcode' => $validated['postcode'] ?? null,
            'country' => $validated['country'] ?? 'GB',
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
            'status' => 'active',
            'last_login_at' => now(),
        ]);

        event(new Registered($user));
        Auth::login($user);

        $this->loadRoleSpecificRelations($user);

        return response()->json([
            'user' => $this->formatUserResponse($user),
            'message' => 'Registration successful'
        ], 201);
    }

    private function loadRoleSpecificRelations(User $user): void
    {
        $relation = match ($user->role) {
            'agency_admin' => 'agency',
            'employer_admin' => 'employerUser.employer',
            'employee' => 'employee',
            'contact' => 'contact.employer',
            'agent' => 'agent.agency',
            default => null
        };

        if ($relation) {
            $user->load($relation);
        }
    }

    private function formatUserResponse(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'display_role' => $this->userRoleService->getDisplayRole($user),
            'contextual_id' => $this->userRoleService->getContextualId($user),
            'permissions' => $this->getUserPermissions($user),
            'profile_complete' => $user->has_complete_profile,
            'address_complete' => $user->has_complete_address,
            'email_verified' => $user->hasVerifiedEmail(),
            'status' => $user->status,
            'phone' => $user->phone,
            'meta' => $user->meta,
        ];
    }

    private function getUserPermissions(User $user): array
    {
        $permissions = [
            'can_approve_timesheets' => $this->permissionService->check($user, 'approve.timesheets'),
            'can_manage_shifts' => $this->permissionService->check($user, 'manage.shifts'),
            'can_view_reports' => $this->permissionService->check($user, 'view.reports'),
            'can_manage_contracts' => $this->permissionService->check($user, 'manage.contracts'),
        ];

        $roleFlags = [
            'is_super_admin' => $user->isSuperAdmin(),
            'is_agency_admin' => $user->role === 'agency_admin',
            'is_employer_admin' => $user->role === 'employer_admin',
            'is_employee' => $user->role === 'employee',
            'is_contact' => $user->role === 'contact',
            'is_agent' => $user->role === 'agent',
        ];

        return array_merge($permissions, $roleFlags);
    }
}
