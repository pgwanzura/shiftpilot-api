<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function store(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['Your account is not active. Please contact support.'],
            ]);
        }

        $user->recordLogin();

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
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    protected function loadRoleSpecificRelations($user)
    {
        $relation = match ($user->role) {
            'agency_admin' => 'agency',
            'employer_admin' => 'employee.employer',
            'employee' => 'employee',
            'contact' => 'contact.employer',
            'agent' => 'agent.agency',
            default => null
        };

        if ($relation) {
            $user->load($relation);
        }
    }

    protected function formatUserResponse($user)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'display_role' => $user->display_role,
            'status' => $user->status,
            'email_verified' => $user->hasVerifiedEmail(),
            'last_login_at' => $user->last_login_at,
            'meta' => $user->meta,
        ];
    }

    protected function getUserPermissions($user)
    {
        return [
            'can_approve_timesheets' => $user->canApproveTimesheets(),
            'can_manage_shifts' => $user->canManageShifts(),
            'can_view_reports' => $user->canViewReports(),
            'can_manage_contracts' => $user->canManageContracts(),
            'is_super_admin' => $user->isSuperAdmin(),
            'is_agency_admin' => $user->isAgencyAdmin(),
            'is_employer_admin' => $user->isEmployerAdmin(),
            'is_employee' => $user->isEmployee(),
            'is_contact' => $user->isContact(),
            'is_agent' => $user->isAgent(),
        ];
    }
}
