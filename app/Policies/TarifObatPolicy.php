<?php

namespace App\Policies;

use App\Models\DrugPrice;
use App\Models\User;

/**
 * TODO(otorisasi): sementara semua pengguna terautentikasi diizinkan.
 * Tarif memengaruhi tagihan; sebaiknya hanya admin/keuangan yang boleh mengubah, peran lain cukup membaca.
 */
class TarifObatPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DrugPrice $tarif): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, DrugPrice $tarif): bool
    {
        return true;
    }
}
