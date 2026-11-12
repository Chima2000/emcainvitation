<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, User $account): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, User $account): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, User $account): bool
    {
        return $user->isSuperAdmin()
            && $user->isNot($account)
            && ! $account->isSuperAdmin();
    }
}
