<?php

namespace App\Enums\Patient;

use App\Support\HasOptions;

enum JenisLayanan: string
{
    use HasOptions;

    case RawatJalan = 'Rawat Jalan';
    case RawatInap = 'Rawat Inap';
    case Igd = 'IGD';
    case Penunjang = 'Penunjang Medis';
}
