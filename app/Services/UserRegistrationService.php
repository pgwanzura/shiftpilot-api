<?php

namespace App\Services;

use App\Contracts\Services\UserRegistrationServiceInterface;
use App\Models\User;
use App\Events\UserRegistered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserRegistrationService implements UserRegistrationServiceInterface
{
    public function registerUser(array $data, string $role): User
    {
        return DB::transaction(function () use ($data, $role) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $role,
                'status' => 'active',
            ]);

            event(new UserRegistered($user));

            return $user;
        });
    }

    public function createUserProfile(User $user, array $profileData): void
    {
        // Polymorphic profile creation based on role
        $profileClass = $this->getProfileClass($user->role);
        $profile = new $profileClass($profileData);
        $user->profile()->save($profile);
    }

    private function getProfileClass(string $role): string
    {
        return match($role) {
            'employer_admin' => \App\Models\EmployerProfile::class,
            'agency_admin' => \App\Models\AgencyProfile::class,
            'employee' => \App\Models\EmployeeProfile::class,
            default => \App\Models\UserProfile::class,
        };
    }
}
