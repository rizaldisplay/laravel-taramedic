<?php

namespace App\Enums\Patient;

use App\Support\HasOptions;

enum Penjamin: string
{
    use HasOptions;

    case Umum = 'Umum';
    case Bpjs = 'BPJS Kesehatan';
    case AsuransiSwasta = 'Asuransi Swasta';

    /** Label singkat untuk badge di daftar pasien. */
    public function ringkas(): string
    {
        return match ($this) {
            self::Umum => 'UMUM',
            self::Bpjs => 'BPJS',
            self::AsuransiSwasta => 'ASURANSI',
        };
    }
}
