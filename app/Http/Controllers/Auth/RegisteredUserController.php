<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', 'in:employee,employer_admin,agency_admin,contact'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'address_line1' => ['sometimes', 'string', 'max:255'],
            'address_line2' => ['sometimes', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:100'],
            'county' => ['sometimes', 'string', 'max:100'],
            'postcode' => ['sometimes', 'string', 'max:20'],
            'country' => ['sometimes', 'string', 'size:2'],
            'date_of_birth' => ['sometimes', 'date'],
            'emergency_contact_name' => ['sometimes', 'string', 'max:255'],
            'emergency_contact_phone' => ['sometimes', 'string', 'max:20'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'phone' => $request->phone ?? null,
            'address_line1' => $request->address_line1 ?? null,
            'address_line2' => $request->address_line2 ?? null,
            'city' => $request->city ?? null,
            'county' => $request->county ?? null,
            'postcode' => $request->postcode ?? null,
            'country' => $request->country ?? 'GB',
            'date_of_birth' => $request->date_of_birth ?? null,
            'emergency_contact_name' => $request->emergency_contact_name ?? null,
            'emergency_contact_phone' => $request->emergency_contact_phone ?? null,
            'status' => 'active',
            'last_login_at' => now(),
        ]);

        event(new Registered($user));
        Auth::login($user);

        $this->loadRoleSpecificRelations($user);

        return response()->json([
            'user' => $user,
            'permissions' => $this->getUserPermissions($user),
            'profile_complete' => $user->has_complete_profile,
            'address_complete' => $user->has_complete_address,
            'message' => 'Registration successful'
        ], 201);
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
