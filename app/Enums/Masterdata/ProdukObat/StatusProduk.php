<?php

namespace App\Enums\Masterdata\ProdukObat;

use App\Support\HasOptions;

enum StatusProduk: string
{
    use HasOptions;

    case Aktif = 'Aktif';
    case Nonaktif = 'Nonaktif';

    public function isActive(): bool
    {
        return $this === self::Aktif;
    }
}
