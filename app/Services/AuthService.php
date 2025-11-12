<?php

namespace App\Services;

use App\Models\User;
use App\Models\Employee;
use App\Models\Agency;
use App\Models\Employer;
use App\Models\Contact;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;

class AuthService
{
    public function registerUser(array $userData): User
    {
        // Create the base user
        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
            'role' => $userData['role'],
            'phone' => $userData['phone'] ?? null,
            'address_line1' => $userData['address_line1'] ?? null,
            'address_line2' => $userData['address_line2'] ?? null,
            'city' => $userData['city'] ?? null,
            'county' => $userData['county'] ?? null,
            'postcode' => $userData['postcode'] ?? null,
            'country' => $userData['country'] ?? 'GB',
            'date_of_birth' => $userData['date_of_birth'] ?? null,
            'emergency_contact_name' => $userData['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $userData['emergency_contact_phone'] ?? null,
            'status' => 'active',
            'last_login_at' => now(),
        ]);

        // Create role-specific profile
        $this->createRoleSpecificProfile($user, $userData);

        event(new Registered($user));

        return $user;
    }

    protected function createRoleSpecificProfile(User $user, array $userData): void
    {
        switch ($user->role) {
            case 'employee':
                Employee::create([
                    'user_id' => $user->id,
                    // Additional employee-specific fields can be added here from $userData if available
                ]);
                break;
            case 'agency_admin':
                Agency::create([
                    'user_id' => $user->id,
                    'name' => $userData['agency_name'] ?? $user->name . ' Agency',
                    // Additional agency-specific fields
                ]);
                break;
            case 'employer_admin':
                Employer::create([
                    'name' => $userData['employer_name'] ?? $user->name . ' Employer',
                    // Additional employer-specific fields
                ]);
                // Also create a contact entry for the employer admin
                Contact::create([
                    'user_id' => $user->id,
                    'employer_id' => $user->employer->id, // Assuming employer is created and associated
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => 'admin',
                ]);
                break;
            case 'contact':
                // For a contact, an employer_id would typically be provided during registration
                // or the contact is created by an employer admin for an existing employer.
                // For now, we assume employer_id is provided or will be associated later.
                Contact::create([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => 'manager',
                    // 'employer_id' => $userData['employer_id'] ?? null, // This would be passed in
                ]);
                break;
        }
    }
}
