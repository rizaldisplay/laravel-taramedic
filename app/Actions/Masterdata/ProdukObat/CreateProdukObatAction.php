<?php

namespace App\Actions\Masterdata\ProdukObat;

use App\DTOs\Masterdata\ProdukObatData;
use App\Events\ProdukObatChanged;
use App\Models\DrugProduct;

final class CreateProdukObatAction
{
    public function execute(ProdukObatData $data, ?int $userId): DrugProduct
    {
        $produk = DrugProduct::create([
            'kfa_code' => $data->kfaCode,
            'kode_lokal' => $data->kodeLokal,
            'name' => $data->nama,
            'generic_name' => $data->namaGenerik,
            'active_ingredient' => $data->zatAktif,
            'strength' => $data->kekuatan,
            'strength_unit' => $data->satuan,
            'dosage_form' => $data->bentukSediaan,
            'routes' => $data->rute,
            'manufacturer' => $data->produsen,
            'formulary_status' => $data->statusFormularium,
            'is_active' => $data->status->isActive(),
            'source' => 'Input manual',
        ]);

        ProdukObatChanged::dispatch($produk, 'dibuat', $userId);

        return $produk;
    }
}
