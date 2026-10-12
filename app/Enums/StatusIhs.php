<?php

namespace App\Enums;

use App\Support\HasOptions;

enum StatusIhs: string
{
    use HasOptions;

    case Belum = "belum";
    case Terhubung = "terhubung";
    case Gagal = "gagal";
}
