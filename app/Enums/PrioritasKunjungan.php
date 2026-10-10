<?php

namespace App\Enums;

use App\Support\Concerns\HasOptions;

enum PrioritasKunjungan: string
{
    use HasOptions;

    case NORMAL = 'NORMAL';
    case PRIORITAS = 'PRIORITAS';
    case DARURAT = 'DARURAT';

    public function label(): string
    {
        return match ($this) {
            self::NORMAL => 'Normal',
            self::PRIORITAS => 'Prioritas (Lansia/Disabilitas/Hamil)',
            self::DARURAT => 'Darurat',
        };
    }
}
