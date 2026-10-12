<?php

namespace App\Policies;

use App\Models\HealthWorker;
use App\Models\User;

/**
 * TODO(otorisasi): sementara semua pengguna terautentikasi diizinkan.
 * Ganti dengan pengecekan peran/izin (mis. hanya admin SDMK/kepegawaian yang boleh mengubah;
 * data NIK, STR, dan SIP bersifat pribadi).
 */
class HealthWorkerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, HealthWorker $worker): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, HealthWorker $worker): bool
    {
        return true;
    }
}
