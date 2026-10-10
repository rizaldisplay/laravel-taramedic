<?php

namespace App\Enums;

use App\Support\Concerns\HasOptions;

enum JenisLayanan: string
{
    use HasOptions;

    case RAWAT_JALAN = 'RAWAT_JALAN';
    case IGD = 'IGD';
    case RAWAT_INAP = 'RAWAT_INAP';
    case PENUNJANG = 'PENUNJANG';

    public function label(): string
    {
        return match ($this) {
            self::RAWAT_JALAN => 'Rawat Jalan',
            self::IGD => 'IGD',
            self::RAWAT_INAP => 'Rawat Inap',
            self::PENUNJANG => 'Penunjang (Lab/Radiologi)',
        };
    }
}
