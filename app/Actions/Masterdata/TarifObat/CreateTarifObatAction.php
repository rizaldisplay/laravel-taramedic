<?php

namespace App\Actions\Masterdata\TarifObat;

use App\DTOs\Masterdata\TarifObatData;
use App\Events\TarifObatChanged;
use App\Exceptions\TarifObatConflictException;
use App\Models\DrugPrice;
use App\Models\DrugProduct;
use Illuminate\Support\Facades\DB;

final class CreateTarifObatAction
{
    public function __construct(
        private readonly EnsureNoTarifOverlapAction $overlap,
        private readonly RecordTarifHistoryAction $history,
    ) {
    }

    public function execute(TarifObatData $data, ?int $userId): DrugPrice
    {
        $tarif = DB::transaction(function () use ($data, $userId) {
            // Kunci baris obat: penulisan tarif untuk obat yang sama berjalan berurutan (pemeriksaan tumpang tindih aman).
            $obat = DrugProduct::whereKey($data->obatId)->lockForUpdate()->firstOrFail();
            if (! $obat->is_active) {
                throw new TarifObatConflictException('obatId', 'Obat nonaktif tidak dapat diberi tarif baru.');
            }

            $this->overlap->execute($data->obatId, $data->penjamin, $data->satuan, $data->berlakuMulai, $data->berlakuSampai);

            $tarif = DrugPrice::create([
                'drug_product_id' => $data->obatId,
                'penjamin' => $data->penjamin,
                'satuan' => $data->satuan,
                'tarif' => $data->tarif,
                'berlaku_mulai' => $data->berlakuMulai,
                'berlaku_sampai' => $data->berlakuSampai,
                'sumber' => $data->sumber,
                'catatan' => $data->catatan,
                'is_active' => true,
            ]);

            $this->history->execute($tarif, 'dibuat', $userId, $this->history->forCreate($tarif));

            return $tarif;
        });

        TarifObatChanged::dispatch($tarif, 'dibuat', $userId); // setelah commit

        return $tarif;
    }
}
