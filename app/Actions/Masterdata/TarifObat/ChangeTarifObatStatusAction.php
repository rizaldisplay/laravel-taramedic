<?php

namespace App\Actions\Masterdata\TarifObat;

use App\Enums\Masterdata\TarifObat\StatusTarif;
use App\Events\TarifObatChanged;
use App\Models\DrugPrice;
use App\Models\DrugProduct;
use Illuminate\Support\Facades\DB;

final class ChangeTarifObatStatusAction
{
    public function __construct(
        private readonly EnsureNoTarifOverlapAction $overlap,
        private readonly RecordTarifHistoryAction $history,
    ) {
    }

    public function execute(DrugPrice $tarif, StatusTarif $status, ?int $userId): DrugPrice
    {
        $berubah = DB::transaction(function () use ($tarif, $status, $userId) {
            DrugProduct::whereKey($tarif->drug_product_id)->lockForUpdate()->first();
            $tarif = DrugPrice::whereKey($tarif->id)->lockForUpdate()->firstOrFail();

            if ($tarif->is_active === $status->isActive()) {
                return [$tarif, false]; // idempoten
            }

            // Mengaktifkan kembali tidak boleh bertabrakan dengan tarif aktif lain yang dibuat sementara itu.
            if ($status->isActive()) {
                $this->overlap->execute(
                    (int) $tarif->drug_product_id, $tarif->penjamin, $tarif->satuan,
                    substr((string) $tarif->berlaku_mulai, 0, 10),
                    $tarif->berlaku_sampai ? substr((string) $tarif->berlaku_sampai, 0, 10) : null,
                    $tarif->id, 'status',
                );
            }

            $tarif->update(['is_active' => $status->isActive()]);
            $this->history->execute($tarif, $status->isActive() ? 'diaktifkan' : 'dinonaktifkan', $userId, $this->history->forStatus($status->isActive()));

            return [$tarif, true];
        });

        [$hasil, $adaPerubahan] = $berubah;
        if ($adaPerubahan) {
            TarifObatChanged::dispatch($hasil, $hasil->is_active ? 'diaktifkan' : 'dinonaktifkan', $userId);
        }

        return $hasil;
    }
}
