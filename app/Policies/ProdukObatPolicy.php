<?php

namespace App\Policies;

use App\Models\DrugProduct;
use App\Models\User;

/**
 * TODO(otorisasi): sementara semua pengguna terautentikasi diizinkan.
 * Master data sebaiknya hanya dapat diubah oleh admin/apoteker; daftar boleh dibaca peran lain.
 */
class ProdukObatPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, DrugProduct $produk): bool
    {
        return true;
    }
}
