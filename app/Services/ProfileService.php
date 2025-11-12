<?php

namespace App\Services;

use App\Models\User;

class ProfileService
{
    public function getUserProfile(User $user): User
    {
        $user->load(['employee', 'agency', 'employer']);
        return $user;
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);

        if (isset($data['employee_data'])) {
            $user->employee()->update($data['employee_data']);
        }

        // Additional logic for updating agency/employer profiles could go here

        return $user->load(['employee', 'agency', 'employer']);
    }
}
