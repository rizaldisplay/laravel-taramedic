<?php

namespace App\Policies;

use App\Models\User;

class VisitPolicy
{
    // TODO: batasi per peran sesuai sistem otorisasi Anda.

    public function create(User $user): bool
    {
        return true;
    }
}
