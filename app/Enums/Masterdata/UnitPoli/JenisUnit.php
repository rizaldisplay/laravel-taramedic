<?php

namespace App\Enums\Masterdata\UnitPoli;

use App\Support\HasOptions;

enum JenisUnit: string
{
    use HasOptions;

    case Poli = 'Poli';
    case Penunjang = 'Penunjang';
    case RawatInap = 'Rawat Inap';
    case Administrasi = 'Administrasi';
    case Lainnya = 'Lainnya';
}
