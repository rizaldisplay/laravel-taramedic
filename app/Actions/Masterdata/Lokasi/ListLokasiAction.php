<?php

namespace App\Actions\Masterdata\Lokasi;

use App\Models\ServiceLocation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class ListLokasiAction
{
    public function execute(?string $search, ?string $status, ?string $tipe, int $page, int $perPage): LengthAwarePaginator
    {
        return ServiceLocation::query()
            ->with(['induk:id,name', 'facility:id,name', 'poli:id,name'])
            ->when($search, fn (Builder $q) => $q->search($search))
            ->when($status, fn (Builder $q) => $q->where('is_active', $status === 'Aktif'))
            ->when($tipe, fn (Builder $q) => $q->where('tipe', $tipe))
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /** @return array{aktif: int, total: int} jumlah keseluruhan (tidak terpengaruh filter). */
    public function summary(): array
    {
        return ['aktif' => ServiceLocation::where('is_active', true)->count(), 'total' => ServiceLocation::count()];
    }
}
