<?php

namespace App\Enums\Masterdata\Lokasi;

use App\Support\HasOptions;

enum StatusLokasi: string
{
    use HasOptions;

    case Aktif = 'Aktif';
    case Nonaktif = 'Nonaktif';

    public function isActive(): bool
    {
        return $this === self::Aktif;
    }
}
