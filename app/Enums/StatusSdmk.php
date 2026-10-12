<?php

namespace App\Enums;

use App\Support\HasOptions;

enum StatusSdmk: string
{
    use HasOptions;

    case Aktif = "Aktif";
    case TidakAktif = "Tidak Aktif";
    case Cuti = "Cuti";
    case BerakhirKontrak = "Berakhir Kontrak";
}
