<?php

namespace App\Enums;

use App\Support\HasOptions;

enum Hari: string
{
    use HasOptions;

    case Senin = "Senin";
    case Selasa = "Selasa";
    case Rabu = "Rabu";
    case Kamis = "Kamis";
    case Jumat = "Jumat";
    case Sabtu = "Sabtu";
    case Minggu = "Minggu";
}
