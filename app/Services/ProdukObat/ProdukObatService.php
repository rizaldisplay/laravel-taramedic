<?php

namespace App\Services\ProdukObat;

use App\Actions\Masterdata\ProdukObat\ChangeProdukObatStatusAction;
use App\Actions\Masterdata\ProdukObat\CreateProdukObatAction;
use App\Actions\Masterdata\ProdukObat\ListProdukObatAction;
use App\Actions\Masterdata\ProdukObat\UpdateProdukObatAction;
use App\DTOs\Masterdata\ProdukObatData;
use App\Enums\Masterdata\ProdukObat\StatusProduk;
use App\Exceptions\ProdukObatConflictException;
use App\Models\DrugProduct;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;

final class ProdukObatService
{
    /** Batas baris endpoint daftar; samakan dengan LIMIT_BARIS di produkObatHelpers.ts. */
    public const MAX_ROWS = 100;

    public function __construct(
        private readonly ListProdukObatAction $listAction,
        private readonly CreateProdukObatAction $createAction,
        private readonly UpdateProdukObatAction $updateAction,
        private readonly ChangeProdukObatStatusAction $statusAction,
    ) {
    }

    /** @return array{items: Collection<int, DrugProduct>, total: int} */
    public function list(?string $search, ?string $status, int $limit): array
    {
        return $this->listAction->execute($search, $status, min($limit, self::MAX_ROWS));
    }

    public function register(ProdukObatData $data, ?int $userId): DrugProduct
    {
        try {
            return $this->createAction->execute($data, $userId);
        } catch (UniqueConstraintViolationException) {
            throw $this->duplicate($data);   // permintaan serentak lolos validasi unique
        }
    }

    public function update(DrugProduct $produk, ProdukObatData $data, ?int $userId): DrugProduct
    {
        try {
            return $this->updateAction->execute($produk, $data, $userId);
        } catch (UniqueConstraintViolationException) {
            throw $this->duplicate($data, $produk->id);
        }
    }

    public function setStatus(DrugProduct $produk, StatusProduk $status, ?int $userId): DrugProduct
    {
        return $this->statusAction->execute($produk, $status, $userId);
    }

    private function duplicate(ProdukObatData $data, ?int $exceptId = null): ProdukObatConflictException
    {
        $kfaDipakai = DrugProduct::where('kfa_code', $data->kfaCode)->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))->exists();

        return $kfaDipakai
            ? new ProdukObatConflictException('kfaCode', 'KFA code sudah digunakan.')
            : new ProdukObatConflictException('kodeLokal', 'Kode lokal sudah digunakan.');
    }
}
