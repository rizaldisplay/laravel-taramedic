<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Pagination\LengthAwarePaginator;

class PatientSearchService
{
    /** Cari berdasarkan nama (contains), No. RM atau NIK (prefix). */
    public function search(?string $keyword, int $perPage = 5): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 50));
        $keyword = trim((string) $keyword);

        return Patient::query()
            ->with('kunjunganTerakhir.poli')
            ->when($keyword !== '', function ($query) use ($keyword) {
                $like = addcslashes($keyword, '%_\\');

                $query->where(function ($w) use ($like) {
                    $w->where('nama_lengkap', 'like', "%{$like}%")
                        ->orWhere('nomor_rm', 'like', "{$like}%")
                        ->orWhere('nik', 'like', "{$like}%");
                });
            })
            ->orderBy('nama_lengkap')
            ->orderBy('nomor_rm')
            ->paginate($perPage);
    }
}
