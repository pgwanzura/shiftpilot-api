<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\EmployeePreferences;
use App\Models\User;

class EmployeePreferencesPolicy
{
    public function view(User $user, EmployeePreferences $preferences): bool
    {
        return $user->employee && $user->employee->id === $preferences->employee_id;
    }

    public function update(User $user, EmployeePreferences $preferences): bool
    {
        return $user->employee && $user->employee->id === $preferences->employee_id;
    }

    public function create(User $user): bool
    {
        return $user->employee !== null;
    }

    public function delete(User $user, EmployeePreferences $preferences): bool
    {
        return $user->employee && $user->employee->id === $preferences->employee_id;
    }
}
