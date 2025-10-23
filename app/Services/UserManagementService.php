<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserManagementService
{
    public function getUsers(array $filters = []): LengthAwarePaginator
    {
        $query = User::with(['agency', 'employer', 'employee', 'agent', 'contact']);

        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            return User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'status' => $data['status'] ?? 'active',
                'email_verified_at' => now(),
            ]);
        });
    }

    public function updateUser(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }

    public function deleteUser(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->delete();
        });
    }

    public function suspendUser(User $user): User
    {
        $user->update([
            'status' => 'suspended',
        ]);

        return $user->fresh();
    }

    public function activateUser(User $user): User
    {
        $user->update([
            'status' => 'active',
        ]);

        return $user->fresh();
    }

    public function changeRole(User $user, string $role): User
    {
        return DB::transaction(function () use ($user, $role) {
            $user->update([
                'role' => $role,
            ]);

            return $user->fresh();
        });
    }

    public function resetPassword(User $user): User
    {
        $tempPassword = Str::random(12);

        $user->update([
            'password' => Hash::make($tempPassword),
        ]);

        return $user->fresh();
    }
}
