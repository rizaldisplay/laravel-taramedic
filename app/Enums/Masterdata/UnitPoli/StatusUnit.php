<?php

namespace App\Enums\Masterdata\UnitPoli;

use App\Support\HasOptions;

enum StatusUnit: string
{
    use HasOptions;

    case Aktif = 'Aktif';
    case Nonaktif = 'Nonaktif';

    public function isActive(): bool
    {
        return $this === self::Aktif;
    }
}
