<?php

namespace App\Actions\Masterdata\TarifObat;

use App\Models\DrugPrice;
use App\Models\DrugProduct;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class ListTarifObatAction
{
    public function execute(?string $search, ?string $penjamin, ?string $status, int $page, int $perPage): LengthAwarePaginator
    {
        return DrugPrice::query()
            ->with('product:id,name,kode_lokal')
            ->when($search, fn (Builder $q) => $q->search($search))
            ->when($penjamin, fn (Builder $q) => $q->where('penjamin', $penjamin))
            ->when($status, fn (Builder $q) => $q->where('is_active', $status === 'Aktif'))
            // Urut menurut nama obat (subquery, tanpa join), lalu penjamin, satuan, dan masa berlaku terbaru dulu.
            ->orderBy(DrugProduct::select('name')->whereColumn('drug_products.id', 'drug_prices.drug_product_id'))
            ->orderBy('penjamin')
            ->orderBy('satuan')
            ->orderByDesc('berlaku_mulai')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
