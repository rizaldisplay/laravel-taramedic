<?php

namespace App\Services\Loket;

use App\Actions\Masterdata\Loket\ChangeLoketStatusAction;
use App\Actions\Masterdata\Loket\CreateLoketAction;
use App\Actions\Masterdata\Loket\ListLoketAction;
use App\Actions\Masterdata\Loket\UpdateLoketAction;
use App\DTOs\Masterdata\LoketData;
use App\Enums\Masterdata\Loket\StatusLoket;
use App\Enums\Masterdata\Loket\TipeLoket;
use App\Exceptions\LoketConflictException;
use App\Models\Counter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;

final class LoketCounterService
{
    public function __construct(
        private readonly ListLoketAction $listAction,
        private readonly CreateLoketAction $createAction,
        private readonly UpdateLoketAction $updateAction,
        private readonly ChangeLoketStatusAction $statusAction,
    ) {
    }

    public function list(?string $search, ?string $status, ?string $tipe, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->listAction->execute($search, $status, $tipe, $page, $perPage);
    }

    /** @return array{aktif: int, total: int} */
    public function summary(): array
    {
        return $this->listAction->summary();
    }

    /** @return array{tipe: \Illuminate\Support\Collection<int, array{value: string, label: string}>} */
    public function options(): array
    {
        return [
            'tipe' => collect(TipeLoket::values())->map(fn (string $v) => ['value' => $v, 'label' => $v])->values(),
        ];
    }

    public function register(LoketData $data, ?int $userId): Counter
    {
        try {
            return $this->createAction->execute($data, $userId);
        } catch (UniqueConstraintViolationException) {
            throw $this->duplicate($data);   // permintaan serentak lolos validasi unique
        }
    }

    public function update(Counter $loket, LoketData $data, ?int $userId): Counter
    {
        try {
            return $this->updateAction->execute($loket, $data, $userId);
        } catch (UniqueConstraintViolationException) {
            throw $this->duplicate($data, $loket->id);
        }
    }

    public function setStatus(Counter $loket, StatusLoket $status, ?int $userId): Counter
    {
        return $this->statusAction->execute($loket, $status, $userId);
    }

    private function duplicate(LoketData $data, ?int $exceptId = null): LoketConflictException
    {
        $kodeDipakai = Counter::where('code', $data->code)->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))->exists();

        return $kodeDipakai
            ? new LoketConflictException('kode', 'Kode sudah digunakan.')
            : new LoketConflictException('nama', 'Nama sudah digunakan.');
    }
}
