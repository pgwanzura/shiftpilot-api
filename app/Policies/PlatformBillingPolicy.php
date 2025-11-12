<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PlatformBilling;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlatformBillingPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
    }

    public function viewAny(User $user)
    {
        // Only Super Admin can view all platform billing records
        return $user->isSuperAdmin();
    }

    public function view(User $user, PlatformBilling $platformBilling)
    {
        // Only Super Admin can view any specific platform billing record
        // Agency Admin might view their own platform fees (handled by InvoicePolicy or specific query)
        return $user->isSuperAdmin();
    }

    public function create(User $user)
    {
        // Only Super Admin can create platform billing records
        return $user->isSuperAdmin();
    }

    public function update(User $user, PlatformBilling $platformBilling)
    {
        // Only Super Admin can update platform billing records
        return $user->isSuperAdmin();
    }

    public function delete(User $user, PlatformBilling $platformBilling)
    {
        // Only Super Admin can delete platform billing records
        return $user->isSuperAdmin();
    }
}
