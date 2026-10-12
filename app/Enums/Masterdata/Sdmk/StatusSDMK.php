<?php 

namespace App\Enums\Masterdata\Sdmk;
use App\Support\Concerns\HasOptions;

enum StatusSDMK: string 
{
    use HasOptions;

    case DRAFT = 'DRAFT';
    case AKTIF = 'AKTIF';
    case TIDAK_AKTIF = 'TIDAK_AKTIF';
    case CUTI = 'CUTI';
    case KONTRAK_BERAKHIR = 'KONTRAK_BERAKHIR';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::AKTIF => 'Aktirf',
            self::TIDAK_AKTIF => 'Tidak Aktif',
            self::CUTI => 'Cuti',
            self::KONTRAK_BERAKHIR => 'Kontrak Berakhir'
        };
    }
}