<?php

namespace App\Enums;

use App\Support\HasOptions;

enum StatusIzin: string
{
    use HasOptions;

    case Aktif = "Aktif";
    case AkanBerakhir = "Akan Berakhir";
    case TidakAktif = "Tidak Aktif";
}
