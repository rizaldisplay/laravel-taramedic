<?php

namespace App\Actions\Masterdata\ProdukObat;

use App\Models\DrugProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ListProdukObatAction
{
    /**
     * Maksimal $limit baris (frontend menyaring/mengurutkan/membagi halaman sendiri atas baris ini).
     * `total` = jumlah yang cocok di database, sehingga UI dapat memberi tahu bila hasil terpotong.
     *
     * @return array{items: Collection<int, DrugProduct>, total: int}
     */
    public function execute(?string $search, ?string $status, int $limit): array
    {
        $query = DrugProduct::query()
            ->when($search, fn (Builder $q) => $q->search($search))
            ->when($status, fn (Builder $q) => $q->where('is_active', $status === 'Aktif'));

        $total = (clone $query)->count();
        $items = $query->orderBy('name')->orderBy('id')->limit($limit)->get();

        return ['items' => $items, 'total' => $total];
    }
}
