<?php

namespace App\Actions\Registrasi\Pasien;

use App\Models\Patient;

final class CheckNIK
{
    /** $exceptId: abaikan pasien ini (saat ubah data, NIK miliknya sendiri bukan duplikat). */
    public function execute(string $nik, ?int $exceptId = null): ?Patient
    {
        return Patient::query()
            ->where('nik', $nik)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->first();
    }
}
