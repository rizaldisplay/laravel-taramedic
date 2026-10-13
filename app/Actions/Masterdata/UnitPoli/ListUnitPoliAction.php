<?php

namespace App\Actions\Masterdata\UnitPoli;

use App\Models\Poli;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class ListUnitPoliAction
{
    public function execute(?string $search, ?string $status, ?string $jenis, int $page, int $perPage): LengthAwarePaginator
    {
        return Poli::query()
            ->with('facility:id,name')
            ->when($search, fn (Builder $q) => $q->search($search))
            ->when($status, fn (Builder $q) => $q->where('is_active', $status === 'Aktif'))
            ->when($jenis, fn (Builder $q) => $q->where('jenis', $jenis))
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /** @return array{aktif: int, total: int} jumlah keseluruhan (tidak terpengaruh filter). */
    public function summary(): array
    {
        return [
            'aktif' => Poli::where('is_active', true)->count(),
            'total' => Poli::count(),
        ];
    }
}
