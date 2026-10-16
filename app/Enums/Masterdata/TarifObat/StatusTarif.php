<?php

namespace App\Enums\Masterdata\TarifObat;

use App\Support\HasOptions;

enum StatusTarif: string
{
    use HasOptions;

    case Aktif = 'Aktif';
    case Nonaktif = 'Nonaktif';

    public function isActive(): bool
    {
        return $this === self::Aktif;
    }
}
