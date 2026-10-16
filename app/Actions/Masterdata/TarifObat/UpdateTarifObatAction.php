<?php

namespace App\Actions\Masterdata\TarifObat;

use App\DTOs\Masterdata\TarifObatData;
use App\Events\TarifObatChanged;
use App\Exceptions\TarifObatConflictException;
use App\Models\DrugPrice;
use App\Models\DrugProduct;
use Illuminate\Support\Facades\DB;

final class UpdateTarifObatAction
{
    public function __construct(
        private readonly EnsureNoTarifOverlapAction $overlap,
        private readonly RecordTarifHistoryAction $history,
    ) {
    }

    public function execute(DrugPrice $tarif, TarifObatData $data, ?int $userId): DrugPrice
    {
        // Obat adalah identitas tarif dan tidak ada kolomnya di riwayat; untuk obat lain buat tarif baru.
        if ($data->obatId !== (int) $tarif->drug_product_id) {
            throw new TarifObatConflictException('obatId', 'Obat tidak dapat diubah. Nonaktifkan tarif ini lalu buat tarif baru untuk obat lain.');
        }

        $berubah = DB::transaction(function () use ($tarif, $data, $userId) {
            DrugProduct::whereKey($tarif->drug_product_id)->lockForUpdate()->first();
            $tarif = DrugPrice::whereKey($tarif->id)->lockForUpdate()->firstOrFail(); // nilai terbaru

            // Tarif nonaktif belum dipakai: tidak diperiksa (dicek saat diaktifkan kembali).
            if ($tarif->is_active) {
                $this->overlap->execute($data->obatId, $data->penjamin, $data->satuan, $data->berlakuMulai, $data->berlakuSampai, $tarif->id);
            }

            $tarif->fill([
                'penjamin' => $data->penjamin,
                'satuan' => $data->satuan,
                'tarif' => $data->tarif,
                'berlaku_mulai' => $data->berlakuMulai,
                'berlaku_sampai' => $data->berlakuSampai,
                'sumber' => $data->sumber,
                'catatan' => $data->catatan,
            ]);

            $perubahan = $this->history->diff($tarif);
            if ($perubahan === []) {
                return [$tarif, false]; // tidak ada yang berubah: tanpa riwayat dan tanpa event
            }

            $tarif->save();
            $this->history->execute($tarif, 'diubah', $userId, $perubahan);

            return [$tarif, true];
        });

        [$hasil, $adaPerubahan] = $berubah;
        if ($adaPerubahan) {
            TarifObatChanged::dispatch($hasil, 'diubah', $userId);
        }

        return $hasil;
    }
}
