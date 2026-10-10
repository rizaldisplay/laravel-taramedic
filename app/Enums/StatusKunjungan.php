<?php

namespace App\Enums;

use App\Support\Concerns\HasOptions;

enum StatusKunjungan: string
{
    use HasOptions;

    case TERDAFTAR = 'TERDAFTAR';
    case DIPERIKSA = 'DIPERIKSA';
    case SELESAI = 'SELESAI';
    case BATAL = 'BATAL';

    public function label(): string
    {
        return match ($this) {
            self::TERDAFTAR => 'Terdaftar',
            self::DIPERIKSA => 'Sedang Diperiksa',
            self::SELESAI => 'Selesai',
            self::BATAL => 'Batal',
        };
    }
}
