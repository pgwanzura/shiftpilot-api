<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\PermissionService;
use App\Services\UserRoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private PermissionService $permissionService,
        private UserRoleService $userRoleService
    ) {}

    public function store(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $user = Auth::user();
        $user->recordLogin();

        $request->session()->regenerate();
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->loadRoleSpecificRelations($user);

        return response()->json([
            'user' => $this->formatUserResponse($user),
            'access_token' => $token,
            'token_type' => 'Bearer',
            'message' => 'Login successful'
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $user->currentAccessToken()?->delete();
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
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
            'email_verified' => $user->hasVerifiedEmail(),
            'status' => $user->status,
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
