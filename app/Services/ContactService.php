<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContactService
{
    public function getContacts(array $filters = []): LengthAwarePaginator
    {
        $query = Contact::with(['employer', 'user']);

        if (isset($filters['employer_id'])) {
            $query->where('employer_id', $filters['employer_id']);
        }

        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function createContact(array $data): Contact
    {
        return DB::transaction(function () use ($data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'role' => 'contact',
                    'status' => 'active',
                    'password' => bcrypt(Str::random(16)),
                ]
            );

            return Contact::create([
                'employer_id' => $data['employer_id'],
                'user_id' => $user->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'role' => $data['role'],
                'can_sign_timesheets' => $data['can_sign_timesheets'] ?? false,
            ]);
        });
    }

    public function updateContact(Contact $contact, array $data): Contact
    {
        return DB::transaction(function () use ($contact, $data) {
            if (isset($data['email']) && $data['email'] !== $contact->email) {
                $user = User::firstOrCreate(
                    ['email' => $data['email']],
                    [
                        'name' => $data['name'] ?? $contact->name,
                        'role' => 'contact',
                        'status' => 'active',
                        'password' => bcrypt(Str::random(16)),
                    ]
                );
                $data['user_id'] = $user->id;
            }

            $contact->update($data);
            return $contact->fresh();
        });
    }

    public function deleteContact(Contact $contact): void
    {
        DB::transaction(function () use ($contact) {
            if ($contact->user) {
                $user = $contact->user;
                if ($user->contacts()->count() === 1) {
                    $user->delete();
                }
            }
            $contact->delete();
        });
    }
}
