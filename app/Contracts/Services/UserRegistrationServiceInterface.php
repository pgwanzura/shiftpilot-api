<?php

namespace App\Contracts\Services;

use App\Models\User;

interface UserRegistrationServiceInterface
{
    public function registerUser(array $data, string $role): User;
    public function createUserProfile(User $user, array $profileData): void;
}
