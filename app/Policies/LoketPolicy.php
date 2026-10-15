<?php

namespace App\Policies;

use App\Models\Counter;
use App\Models\User;

/**
 * TODO(otorisasi): sementara semua pengguna terautentikasi diizinkan.
 * Master data sebaiknya hanya dapat diubah oleh admin; daftar boleh dibaca peran lain.
 */
class LoketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Counter $loket): bool
    {
        return true;
    }
}
