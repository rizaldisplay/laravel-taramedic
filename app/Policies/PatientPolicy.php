<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    // TODO: batasi per peran (mis. hanya petugas pendaftaran) sesuai
    // sistem otorisasi Anda, misalnya $user->hasRole('petugas-pendaftaran').

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Patient $patient): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Patient $patient): bool
    {
        return true;
    }
}
