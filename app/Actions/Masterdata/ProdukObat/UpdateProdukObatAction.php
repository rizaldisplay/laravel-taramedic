<?php

namespace App\Actions\Masterdata\ProdukObat;

use App\DTOs\Masterdata\ProdukObatData;
use App\Events\ProdukObatChanged;
use App\Exceptions\ProdukObatConflictException;
use App\Models\DrugProduct;

final class UpdateProdukObatAction
{
    public function execute(DrugProduct $produk, ProdukObatData $data, ?int $userId): DrugProduct
    {
        // KFA code = identitas sumber; frontend mengirimnya kembali apa adanya.
        if ($data->kfaCode !== $produk->kfa_code) {
            throw new ProdukObatConflictException('kfaCode', 'KFA code tidak dapat diubah setelah produk dibuat.');
        }

        $produk->update([
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
        ]);

        ProdukObatChanged::dispatch($produk, 'diubah', $userId);

        return $produk;
    }
}
