<?php

namespace App\Services;

use App\Actions\Masterdata\UnitPoli\ChangeUnitPoliStatusAction;
use App\Actions\Masterdata\UnitPoli\CreateUnitPoliAction;
use App\Actions\Masterdata\UnitPoli\ListUnitPoliAction;
use App\Actions\Masterdata\UnitPoli\UpdateUnitPoliAction;
use App\DTOs\Masterdata\UnitPoliData;
use App\Enums\Masterdata\UnitPoli\JenisUnit;
use App\Enums\Masterdata\UnitPoli\StatusUnit;
use App\Exceptions\UnitPoliConflictException;
use App\Models\Facility;
use App\Models\Poli;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;

final class UnitPoliService
{
    public function __construct(
        private readonly ListUnitPoliAction $list,
        private readonly CreateUnitPoliAction $create,
        private readonly UpdateUnitPoliAction $update,
        private readonly ChangeUnitPoliStatusAction $changeStatus,
    ) {
    }

    public function list(?string $search, ?string $status, ?string $jenis, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->list->execute($search, $status, $jenis, $page, $perPage);
    }

    /** @return array{aktif: int, total: int} */
    public function summary(): array
    {
        return $this->list->summary();
    }

    /** @return array<string, mixed> */
    public function options(): array
    {
        return [
            'fasyankes' => Facility::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($f) => ['value' => (string) $f->id, 'label' => $f->name])->values(),
            'jenis' => collect(JenisUnit::values())->map(fn ($v) => ['value' => $v, 'label' => $v])->values(),
        ];
    }

    public function register(UnitPoliData $data, ?int $userId): Poli
    {
        try {
            return $this->create->execute($data, $userId)->load('facility:id,name');
        } catch (UniqueConstraintViolationException) {
            throw $this->duplicate($data);   // permintaan serentak lolos validasi unique
        }
    }

    public function update(Poli $poli, UnitPoliData $data, ?int $userId): Poli
    {
        try {
            return $this->update->execute($poli, $data, $userId)->load('facility:id,name');
        } catch (UniqueConstraintViolationException) {
            throw $this->duplicate($data, $poli->id);
        }
    }

    public function setStatus(Poli $poli, StatusUnit $status, ?int $userId): Poli
    {
        return $this->changeStatus->execute($poli, $status, $userId)->load('facility:id,name');
    }

    private function duplicate(UnitPoliData $data, ?int $exceptId = null): UnitPoliConflictException
    {
        $kodeDipakai = Poli::where('kode', $data->kode)->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))->exists();

        return $kodeDipakai
            ? new UnitPoliConflictException('kode', 'Kode sudah digunakan.')
            : new UnitPoliConflictException('nama', 'Nama sudah digunakan.');
    }
}
