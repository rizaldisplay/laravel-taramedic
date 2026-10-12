<?php 

namespace App\Enums\Masterdata\Sdmk;
use App\Support\Concerns\HasOptions;

enum StatusSTR: string 
{
    use HasOptions;

    case VALID = 'VALID';
    case KADALUARSA = 'KADALUARSA';
    case MENUNGGU_VERIFIKASI = 'MENUNGGU_VERIFIKASI';

    public function label(): string
    {
        return match ($this) {
            self::VALID => 'Valid',
            self::KADALUARSA => 'Kadaluarsa',
            self::MENUNGGU_VERIFIKASI => 'Menunggu Verifikasi',
        };
    }
}