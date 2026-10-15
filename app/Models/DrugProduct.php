<?php

namespace App\Models;

use App\Enums\Masterdata\ProdukObat\StatusFormularium;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Produk obat = snapshot lokal dari KFA (Kamus Farmasi dan Alat Kesehatan).
 *
 * `kfa_code` adalah identitas sumber dan tidak berubah setelah dibuat. Produk tidak pernah dihapus,
 * hanya dinonaktifkan, supaya resep/riwayat yang merujuk `id` tetap utuh.
 * `brand_name`, `registration_number`, `synced_at` hanya diisi oleh sinkronisasi KFA (bukan dari form).
 */
class DrugProduct extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'routes' => 'array',
        'is_active' => 'boolean',
        'formulary_status' => StatusFormularium::class,
        'synced_at' => 'datetime',
    ];

    /** Pencarian (tidak peka huruf besar/kecil) pada nama, generik, dagang, zat aktif, kode KFA, dan kode lokal. */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%' . addcslashes(mb_strtolower($term), '%_\\') . '%';

        return $query->where(function (Builder $q) use ($like) {
            foreach (['name', 'generic_name', 'brand_name', 'active_ingredient', 'kfa_code', 'kode_lokal'] as $kolom) {
                $q->orWhereRaw("LOWER({$kolom}) LIKE ?", [$like]);
            }
        });
    }
}
