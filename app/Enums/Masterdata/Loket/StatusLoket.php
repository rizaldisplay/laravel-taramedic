<?php

namespace App\Enums\Masterdata\Loket;

use App\Support\HasOptions;

enum StatusLoket: string
{
    use HasOptions;

    case Aktif = 'Aktif';
    case Nonaktif = 'Nonaktif';

    public function isActive(): bool
    {
        return $this === self::Aktif;
    }
}
