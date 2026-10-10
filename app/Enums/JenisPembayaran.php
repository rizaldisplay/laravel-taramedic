<?php

namespace App\Enums;

use App\Support\Concerns\HasOptions;

enum JenisPembayaran: string
{
    use HasOptions;

    case BPJS = 'BPJS';
    case UMUM = 'UMUM';
    case ASURANSI = 'ASURANSI';

    public function label(): string
    {
        return match ($this) {
            self::BPJS => 'BPJS Kesehatan',
            self::UMUM => 'Umum / Tunai',
            self::ASURANSI => 'Asuransi Swasta',
        };
    }
}
