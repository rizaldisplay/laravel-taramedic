<?php

namespace App\Models;

use App\Enums\Patient\Penjamin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tarif obat per penjamin + satuan dengan masa berlaku. Untuk invoice internal, BUKAN nilai klaim BPJS/asuransi.
 *
 * Aturan: tarif AKTIF untuk obat + penjamin + satuan yang sama tidak boleh punya masa berlaku yang beririsan
 * (dijaga di App\Actions\TarifObat\EnsureNoTarifOverlapAction). Tarif tidak pernah dihapus, hanya dinonaktifkan.
 *
 * Kolom tanggal sengaja TIDAK di-cast: tetap string "YYYY-MM-DD" seperti yang dibaca/ditulis frontend.
 */
class DrugPrice extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'penjamin' => Penjamin::class,
        'tarif' => 'integer',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(DrugProduct::class, 'drug_product_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(DrugPriceHistory::class)->orderByDesc('id');
    }

    /** Pencarian pada nama/kode/nama generik obat dan satuan (tidak peka huruf besar/kecil). */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%' . addcslashes(mb_strtolower($term), '%_\\') . '%';

        return $query->where(function (Builder $q) use ($like) {
            $q->whereRaw('LOWER(drug_prices.satuan) LIKE ?', [$like])
                ->orWhereHas('product', function (Builder $p) use ($like) {
                    $p->where(function (Builder $w) use ($like) {
                        foreach (['name', 'generic_name', 'kode_lokal', 'kfa_code'] as $kolom) {
                            $w->orWhereRaw("LOWER({$kolom}) LIKE ?", [$like]);
                        }
                    });
                });
        });
    }
}
