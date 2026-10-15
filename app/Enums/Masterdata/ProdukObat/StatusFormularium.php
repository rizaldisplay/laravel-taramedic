<?php

namespace App\Enums\Masterdata\ProdukObat;

use App\Support\HasOptions;

/** Nilai = teks yang dipakai UI (OPSI_FORMULARIUM pada produkObatHelpers.ts). */
enum StatusFormularium: string
{
    use HasOptions;

    case Formularium = 'Formularium';
    case NonFormularium = 'Non-formularium';
    case PerluPersetujuan = 'Perlu persetujuan';
}
