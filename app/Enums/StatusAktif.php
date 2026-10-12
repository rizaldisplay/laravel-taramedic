<?php

namespace App\Enums;

use App\Support\HasOptions;

/** Status penempatan. */
enum StatusAktif: string
{
    use HasOptions;

    case Aktif = "Aktif";
    case Nonaktif = "Nonaktif";
}
