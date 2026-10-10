<?php

namespace App\Enums\Patient;

use App\Support\HasOptions;

enum Prioritas: string
{
    use HasOptions;

    case Normal = 'Normal';
    case Prioritas = 'Prioritas';
    case Darurat = 'Darurat';
}
