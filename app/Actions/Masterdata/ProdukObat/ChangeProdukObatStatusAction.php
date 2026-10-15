<?php

namespace App\Actions\Masterdata\ProdukObat;

use App\Enums\Masterdata\ProdukObat\StatusProduk;
use App\Events\ProdukObatChanged;
use App\Models\DrugProduct;

final class ChangeProdukObatStatusAction
{
    public function execute(DrugProduct $produk, StatusProduk $status, ?int $userId): DrugProduct
    {
        if ($produk->is_active === $status->isActive()) {
            return $produk; // idempoten
        }

        $produk->update(['is_active' => $status->isActive()]);

        ProdukObatChanged::dispatch($produk, $status->isActive() ? 'diaktifkan' : 'dinonaktifkan', $userId);

        return $produk;
    }
}
