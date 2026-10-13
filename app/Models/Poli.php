<?php

namespace App\Models;

use App\Enums\Masterdata\UnitPoli\JenisUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// MENGGANTIKAN app/Models/Poli.php dari paket pendaftaran/SDMK (superset: relasi facility + kolom unit).
class Poli extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'untuk_antrean' => 'boolean',
        'jenis' => JenisUnit::class,
    ];

    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /** Pencarian kode atau nama. */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%' . addcslashes($term, '%_\\') . '%';

        return $query->where(fn (Builder $q) => $q->where('name', 'like', $like)->orWhere('kode', 'like', $like));
    }
}
