<?php 

namespace App\Enums\Masterdata\Sdmk;
use App\Support\Concerns\HasOptions;

enum JenisSDMK: string 
{
    use HasOptions;

    case TENAGA_MEDIS = 'TENAGA_MEDIS';
    case TENAGA_KESEHATAN = 'TENAGA_KESEHATAN';
    case TENAGA_PENDUKUNG = 'TENAGA PENDUKUNG';
    public function label(): string
    {
        return match ($this) {
            self::TENAGA_MEDIS => 'Tenaga Medis',
            self::TENAGA_KESEHATAN => 'Rujukan Fasyankes Lain',
            self::TENAGA_PENDUKUNG => 'Kontrol Ulang',
        };
    }
}