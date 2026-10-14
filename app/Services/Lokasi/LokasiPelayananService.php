<?php

namespace App\Services\Lokasi;

use App\Actions\Masterdata\Lokasi\ChangeLokasiStatusAction;
use App\Actions\Masterdata\Lokasi\CreateLokasiAction;
use App\Actions\Masterdata\Lokasi\ListLokasiAction;
use App\Actions\Masterdata\Lokasi\UpdateLokasiAction;
use App\DTOs\Masterdata\LokasiData;
use App\Enums\Masterdata\Lokasi\StatusLokasi;
use App\Enums\Masterdata\Lokasi\TipeLokasi;
use App\Exceptions\LokasiConflictException;
use App\Models\Facility;
use App\Models\Poli;
use App\Models\ServiceLocation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;

final class LokasiPelayananService
{
    public function __construct(
        private readonly ListLokasiAction $listAction,
        private readonly CreateLokasiAction $createAction,
        private readonly UpdateLokasiAction $updateAction,
        private readonly ChangeLokasiStatusAction $statusAction,
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

    /** @return array<string, mixed> */
    public function options(): array
    {
        return [
            'fasyankes' => Facility::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($f) => ['value' => (string) $f->id, 'label' => $f->name])->values(),
            'poli' => Poli::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($p) => ['value' => (string) $p->id, 'label' => $p->name])->values(),
            'induk' => ServiceLocation::where('is_active', true)->orderBy('name')->get(['id', 'name', 'induk_id'])
                ->map(fn ($l) => ['value' => (string) $l->id, 'label' => $l->name, 'indukId' => $l->induk_id ? (string) $l->induk_id : null])->values(),
            'tipe' => collect(TipeLokasi::values())->map(fn ($v) => ['value' => $v, 'label' => $v])->values(),
        ];
    }

    public function register(LokasiData $data, ?int $userId): ServiceLocation
    {
        try {
            return $this->createAction->execute($data, $userId)->load(['induk:id,name', 'facility:id,name', 'poli:id,name']);
        } catch (UniqueConstraintViolationException) {
            throw $this->duplicate($data);   // permintaan serentak lolos validasi unique
        }
    }

    public function update(ServiceLocation $lokasi, LokasiData $data, ?int $userId): ServiceLocation
    {
        try {
            return $this->updateAction->execute($lokasi, $data, $userId)->load(['induk:id,name', 'facility:id,name', 'poli:id,name']);
        } catch (UniqueConstraintViolationException) {
            throw $this->duplicate($data, $lokasi->id);
        }
    }

    public function setStatus(ServiceLocation $lokasi, StatusLokasi $status, ?int $userId): ServiceLocation
    {
        return $this->statusAction->execute($lokasi, $status, $userId)->load(['induk:id,name', 'facility:id,name', 'poli:id,name']);
    }

    private function duplicate(LokasiData $data, ?int $exceptId = null): LokasiConflictException
    {
        $kodeDipakai = ServiceLocation::where('kode', $data->kode)->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))->exists();

        return $kodeDipakai
            ? new LokasiConflictException('kode', 'Kode sudah digunakan.')
            : new LokasiConflictException('nama', 'Nama sudah digunakan.');
    }
}
