<?php

namespace App\Support\Concerns;

use Illuminate\Database\Eloquent\Builder;

/** Untuk model master yang punya kolom boolean `aktif`. */
trait ScopeAktif
{
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('aktif'), true);
    }
}
