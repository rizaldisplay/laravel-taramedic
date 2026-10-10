<?php

namespace App\Enums\Patient;

use App\Support\HasOptions;

enum JenisPembayaran: string
{
    use HasOptions;

    case Tunai = 'Tunai';
    case NonTunai = 'Non-Tunai';
    case KlaimPenjamin = 'Klaim Penjamin';
}
