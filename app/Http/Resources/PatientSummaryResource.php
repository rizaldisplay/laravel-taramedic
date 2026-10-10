<?php

namespace App\Http\Resources;

use App\Enums\Patient\Penjamin;
use App\Support\Age;
use App\Support\Mask;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Bentuk response = type `PatientListItem` di frontend. Semua teks siap tampil,
 * data pribadi (NIK, HP) disamarkan. Eager load: latestVisit.poli
 *
 * @mixin \App\Models\Patient
 */
class PatientSummaryResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $visit = $this->latestVisit;
        $penjamin = $visit?->penjamin ?? Penjamin::Umum;

        return [
            'id' => $this->id,
            'nomorRM' => $this->nomor_rm,
            'namaLengkap' => $this->nama_lengkap,
            'inisial' => Str::of($this->nama_lengkap)->explode(' ')->filter()->take(2)
                ->map(fn (string $w) => Str::upper(Str::substr($w, 0, 1)))->implode(''),
            'jenisKelamin' => $this->jenis_kelamin->value,
            'usiaString' => Age::format($this->tanggal_lahir),
            'tanggalLahir' => $this->tanggal_lahir->format('d-m-Y'),
            'nik' => Mask::identity($this->nik ?? $this->nomor_paspor),
            'nomorHP' => Mask::phone($this->nomor_hp),
            'alamatRingkas' => collect([$this->alamat_lengkap, $this->regency_name])->filter()->implode(', '),
            'isActive' => $this->is_active,
            'penjaminUtama' => $penjamin->ringkas(),
            'penjaminLabel' => $penjamin->value,
            'tanggalKunjunganTerakhir' => $visit?->registered_at?->format('d-m-Y'),
            'poliKunjunganTerakhir' => $visit?->poli?->name,
            'satuSehat' => [
                'statusTerhubung' => $this->ihs_number !== null,
                'ihsNumber' => $this->ihs_number,
                'lastSyncAt' => $this->satusehat_synced_at
                    ? $this->satusehat_synced_at->format('d-m-Y H:i') . ' WIB'
                    : null,
            ],
        ];
    }
}
