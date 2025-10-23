<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    /**
     * Get the authenticated user with role data
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // Load relationships based on role
        $this->loadRoleSpecificRelations($user);

        return response()->json([
            'user' => $user,
            'permissions' => $this->getUserPermissions($user),
            'profile_complete' => $user->has_complete_profile,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'date_of_birth' => ['sometimes', 'date'],
            'emergency_contact_name' => ['sometimes', 'string', 'max:255'],
            'emergency_contact_phone' => ['sometimes', 'string', 'max:20'],
            'address' => ['sometimes', 'string'],
        ]);

        $user->update($validated);

        $this->loadRoleSpecificRelations($user);

        return response()->json([
            'user' => $user,
            'profile_complete' => $user->has_complete_profile,
            'message' => 'Profile updated successfully'
        ]);
    }

    protected function loadRoleSpecificRelations(User $user)
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


    protected function getUserPermissions(User $user)
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
