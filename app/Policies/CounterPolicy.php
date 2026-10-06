<?php

namespace App\Policies;

use App\Models\Counter;
use App\Models\User;

class CounterPolicy
{
    /**
     * Whether the user may operate (call/serve) this counter.
     */
    public function operate(User $user, Counter $counter): bool
    {
        if ($user->role->canAccessAdmin()) {
            return true;
        }

        if (! $user->role->canAccessOperator()) {
            return false;
        }

        return $counter->claimed_by_user_id === $user->id;
    }

    /**
     * Whether the user may forcibly claim a counter held by someone else.
     */
    public function forceClaim(User $user, Counter $counter): bool
    {
        return $user->role->canAccessAdmin();
    }
}
