<?php

namespace App\Enums\Masterdata\Lokasi;

use App\Support\HasOptions;

enum TipeLokasi: string
{
    use HasOptions;

    case Gedung = 'Gedung';
    case Lantai = 'Lantai';
    case Ruang = 'Ruang';
    case AreaLainnya = 'Area Lainnya';
}
