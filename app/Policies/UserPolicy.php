<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->canAccessAdmin();
    }

    public function create(User $user): bool
    {
        return $user->role->canAccessAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->role->canAccessAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        if (! $user->role->canAccessAdmin()) {
            return false;
        }

        if ($user->id === $model->id) {
            return false;
        }

        if ($model->role === UserRole::Admin) {
            return User::query()
                ->where('role', UserRole::Admin)
                ->whereKeyNot($model->id)
                ->exists();
        }

        return true;
    }
}
