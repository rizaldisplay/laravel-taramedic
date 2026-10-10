<?php

namespace App\Enums\Patient;

use App\Support\HasOptions;

enum CaraMasuk: string
{
    use HasOptions;

    case DatangSendiri = 'Datang Sendiri';
    case RujukanFaskes = 'Rujukan Faskes Tingkat Pertama';
    case RujukanRs = 'Rujukan Rumah Sakit';
    case Kontrol = 'Kontrol Ulang';
}
