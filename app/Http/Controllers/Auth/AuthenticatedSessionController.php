<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    public function store(LoginRequest $request): JsonResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = Auth::user();
        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('auth-token')->plainTextToken;

        $this->loadRoleSpecificRelations($user);

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'permissions' => $this->getUserPermissions($user),
            'profile_complete' => $user->has_complete_profile,
            'message' => 'Login successful'
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    protected function loadRoleSpecificRelations($user)
    {
        $relations = [];

        switch ($user->role) {
            case 'agency_admin':
                $relations[] = 'agency';
                break;
            case 'employer_admin':
                $relations[] = 'employer';
                break;
            case 'employee':
                $relations[] = 'employee';
                break;
            case 'contact':
                $relations[] = 'contact';
                break;
        }

        if (!empty($relations)) {
            $user->load($relations);
        }
    }

    protected function getUserPermissions($user)
    {
        return [
            'can_approve_timesheets' => $user->canApproveTimesheets(),
            'can_manage_shifts' => $user->canManageShifts(),
            'is_super_admin' => $user->isSuperAdmin(),
            'is_agency_admin' => $user->isAgencyAdmin(),
            'is_employer_admin' => $user->isEmployerAdmin(),
            'is_employee' => $user->isEmployee(),
            'is_contact' => $user->isContact(),
        ];
    }
}
