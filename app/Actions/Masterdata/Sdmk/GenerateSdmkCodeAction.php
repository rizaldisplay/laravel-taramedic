<?php

namespace App\Actions\Masterdata\Sdmk;

use App\Support\Sequence;

final class GenerateSdmkCodeAction
{
    /** Wajib dipanggil di dalam DB::transaction (lihat Sequence::next). Hasil: SDMK-000001 */
    public function execute(): string
    {
        return config('sdmk.code_prefix') . str_pad((string) Sequence::next('sdmk_code'), 6, '0', STR_PAD_LEFT);
    }
}
