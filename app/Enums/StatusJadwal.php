<?php

namespace App\Enums;

use App\Support\HasOptions;

enum StatusJadwal: string
{
    use HasOptions;

    case Aktif = "Aktif";
    case TidakAktif = "Tidak Aktif";
}
