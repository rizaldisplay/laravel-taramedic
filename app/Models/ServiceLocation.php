<?php

namespace App\Models;

use App\Enums\Masterdata\Lokasi\TipeLokasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// MENGGANTIKAN app/Models/ServiceLocation.php dari paket pendaftaran.
class ServiceLocation extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean', 'tipe' => TipeLokasi::class];

    public function induk(): BelongsTo
    {
        return $this->belongsTo(self::class, 'induk_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'induk_id');
    }

    public function poli(): BelongsTo
    {
        return $this->belongsTo(Poli::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * Id seluruh turunan (anak, cucu, ...). Dipakai untuk mencegah siklus dan menurunkan fasyankes.
     * Penelusuran per tingkat; himpunan "sudah dikunjungi" menjaga dari loop bila data sempat rusak.
     *
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $seen = [$this->id => true];
        $frontier = [$this->id];
        $result = [];

        while ($frontier) {
            $next = self::query()->whereIn('induk_id', $frontier)->pluck('id')->all();
            $frontier = [];
            foreach ($next as $id) {
                if (! isset($seen[$id])) {
                    $seen[$id] = true;
                    $result[] = $id;
                    $frontier[] = $id;
                }
            }
        }

        return $result;
    }

    /** Lokasi yang boleh dipilih untuk layanan/kunjungan: aktif dan bukan Gedung/Lantai (hanya pengelompokan). */
    public function scopeDapatDipilih(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereNotIn('tipe', [TipeLokasi::Gedung->value, TipeLokasi::Lantai->value]);
    }

    /** Pencarian kode atau nama. */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%' . addcslashes($term, '%_\\') . '%';

        return $query->where(fn (Builder $q) => $q->where('name', 'like', $like)->orWhere('kode', 'like', $like));
    }
}
