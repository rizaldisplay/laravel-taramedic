<?php

namespace App\Enums;

use App\Support\Concerns\HasOptions;

enum CaraMasuk: string
{
    use HasOptions;

    case DATANG_SENDIRI = 'DATANG_SENDIRI';
    case RUJUKAN = 'RUJUKAN';
    case KONTROL = 'KONTROL';
    case RUJUKAN_INTERNAL = 'RUJUKAN_INTERNAL';

    public function label(): string
    {
        return match ($this) {
            self::DATANG_SENDIRI => 'Datang Sendiri',
            self::RUJUKAN => 'Rujukan Fasyankes Lain',
            self::KONTROL => 'Kontrol Ulang',
            self::RUJUKAN_INTERNAL => 'Rujukan Internal',
        };
    }
}
