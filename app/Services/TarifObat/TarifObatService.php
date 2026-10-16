<?php

namespace App\Services\TarifObat;

use App\Actions\Masterdata\TarifObat\ChangeTarifObatStatusAction;
use App\Actions\Masterdata\TarifObat\CreateTarifObatAction;
use App\Actions\Masterdata\TarifObat\ListTarifObatAction;
use App\Actions\Masterdata\TarifObat\UpdateTarifObatAction;
use App\DTOs\Masterdata\TarifObatData;
use App\Enums\Patient\Penjamin;
use App\Enums\Masterdata\TarifObat\StatusTarif;
use App\Models\DrugPrice;
use App\Models\DrugPriceHistory;
use App\Models\DrugProduct;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class TarifObatService
{
    /** Batas baris riwayat yang dikirim per tarif (terbaru dulu). */
    public const MAX_RIWAYAT = 100;

    public function __construct(
        private readonly ListTarifObatAction $listAction,
        private readonly CreateTarifObatAction $createAction,
        private readonly UpdateTarifObatAction $updateAction,
        private readonly ChangeTarifObatStatusAction $statusAction,
    ) {
    }

    public function list(?string $search, ?string $penjamin, ?string $status, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->listAction->execute($search, $penjamin, $status, $page, $perPage);
    }

    /**
     * Obat aktif untuk dropdown. `value` = id obat (string), `label` = "KODE · Nama" (sama dengan label di form edit),
     * `satuan` = saran satuan dari bentuk sediaan (huruf kecil) untuk mengisi form otomatis.
     *
     * @return array{obat: Collection<int, array<string, string>>, penjamin: list<string>}
     */
    public function options(): array
    {
        return [
            'obat' => DrugProduct::where('is_active', true)->orderBy('name')->get(['id', 'name', 'kode_lokal', 'dosage_form'])
                ->map(fn (DrugProduct $o) => [
                    'value' => (string) $o->id,
                    'label' => $o->kode_lokal . ' · ' . $o->name,
                    'satuan' => mb_strtolower((string) $o->dosage_form),
                ])->values(),
            'penjamin' => Penjamin::values(),
        ];
    }

    public function register(TarifObatData $data, ?int $userId): DrugPrice
    {
        return $this->createAction->execute($data, $userId)->load('product:id,name,kode_lokal');
    }

    public function update(DrugPrice $tarif, TarifObatData $data, ?int $userId): DrugPrice
    {
        return $this->updateAction->execute($tarif, $data, $userId)->load('product:id,name,kode_lokal');
    }

    public function setStatus(DrugPrice $tarif, StatusTarif $status, ?int $userId): DrugPrice
    {
        return $this->statusAction->execute($tarif, $status, $userId)->load('product:id,name,kode_lokal');
    }

    /** @return Collection<int, DrugPriceHistory> terbaru dulu */
    public function riwayat(DrugPrice $tarif): Collection
    {
        return $tarif->histories()->limit(self::MAX_RIWAYAT)->get();
    }
}
