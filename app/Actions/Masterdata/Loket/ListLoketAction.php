<?php

namespace App\Actions\Masterdata\Loket;

use App\Models\Counter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class ListLoketAction
{
    public function execute(?string $search, ?string $status, ?string $tipe, int $page, int $perPage): LengthAwarePaginator
    {
        return Counter::query()
            ->when($search, fn (Builder $q) => $q->search($search))
            ->when($status, fn (Builder $q) => $q->where('is_active', $status === 'Aktif'))
            ->when($tipe, fn (Builder $q) => $q->where('tipe', $tipe))
            ->orderBy('id') // urutan nomor loket (Loket 2 sebelum Loket 10)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /** @return array{aktif: int, total: int} jumlah keseluruhan (tidak terpengaruh filter). */
    public function summary(): array
    {
        return ['aktif' => Counter::where('is_active', true)->count(), 'total' => Counter::count()];
    }
}
