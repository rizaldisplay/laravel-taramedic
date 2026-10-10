<?php

namespace App\Actions\Registrasi\Pasien;

use App\Models\Patient;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class SearchPatients
{
    /** Cari berdasarkan nama, No. RM, NIK/paspor, atau No. HP. Tanpa kata kunci: pasien terbaru dulu. */
    public function execute(string $term, int $page, int $perPage): LengthAwarePaginator
    {
        $query = Patient::query()->with('latestVisit.poli');

        if ($term === '') {
            $query->orderByDesc('id');
        } else {
            // Escape karakter wildcard LIKE agar "%" atau "_" dicari apa adanya.
            $like = addcslashes($term, '%_\\');
            $rm = strtoupper($like);

            $query->where(function (Builder $q) use ($like, $rm) {
                $q->where('nama_lengkap', 'like', "%{$like}%")
                    ->orWhere('nomor_rm', 'like', "{$rm}%")
                    ->orWhere('nik', 'like', "{$like}%")
                    ->orWhere('nomor_paspor', 'like', "{$rm}%")
                    ->orWhere('nomor_hp', 'like', "{$like}%");
            })->orderBy('nama_lengkap')->orderBy('id');
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
