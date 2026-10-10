<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk response = type `PatientDetail` di frontend: data lengkap (tidak disamarkan)
 * + nama wilayah + info sistem. Eager load: creator
 *
 * @mixin \App\Models\Patient
 */
class PatientDetailResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $sync = $this->satusehat_synced_at;

        return [
            ...(new PatientResource($this->resource))->toArray($request),
            'wilayahNama' => [
                'provinsi' => $this->province_name ?? '',
                'kabupatenKota' => $this->regency_name ?? '',
                'kecamatan' => $this->district_name ?? '',
                'desaKelurahan' => $this->village_name ?? '',
            ],
            'isActive' => $this->is_active,
            'nikTerverifikasi' => $this->nik_verified_at !== null,
            'satuSehat' => [
                'statusTerhubung' => $this->ihs_number !== null,
                'ihsNumber' => $this->ihs_number,
                'lastSyncAt' => $sync ? $sync->format('d-m-Y H:i') . ' WIB' : null,
            ],
            'tanggalRegistrasi' => $this->created_at->format('d-m-Y H:i'),
            'terakhirDiperbarui' => $this->updated_at->format('d-m-Y H:i'),
            'dibuatOleh' => $this->creator?->name ?? '-',
            'sumberRegistrasi' => $this->registration_source,
        ];
    }
}
