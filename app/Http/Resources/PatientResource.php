<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk response = type `Pendaftaran` di frontend (camelCase, tanpa pembungkus "data").
 * @mixin \App\Models\Patient
 */
class PatientResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nomorRM' => $this->nomor_rm,
            'jenisIdentitas' => $this->jenis_identitas->value,
            'nik' => $this->nik ?? $this->nomor_paspor ?? '',
            'namaLengkap' => $this->nama_lengkap,
            'tempatLahir' => $this->tempat_lahir ?? '',
            'tanggalLahir' => $this->tanggal_lahir->format('Y-m-d'),
            'jenisKelamin' => $this->jenis_kelamin->value,
            'alamatLengkap' => $this->alamat_lengkap,
            'provinsi' => $this->province_code,
            'kabupatenKota' => $this->regency_code,
            'kecamatan' => $this->district_code,
            'desaKelurahan' => $this->village_code,
            'rt' => $this->rt ?? '',
            'rw' => $this->rw ?? '',
            'kodePos' => $this->kode_pos ?? '',
            'nomorHP' => $this->nomor_hp,
            'email' => $this->email ?? '',
            'nomorKK' => $this->nomor_kk ?? '',
            'statusPerkawinan' => $this->status_perkawinan ?? '',
            'kewarganegaraan' => $this->kewarganegaraan,
            'bahasa' => $this->bahasa ?? '',
            'namaKontakDarurat' => $this->nama_kontak_darurat ?? '',
            'hubunganKontakDarurat' => $this->hubungan_kontak_darurat ?? '',
            'nomorHPKontakDarurat' => $this->nomor_hp_kontak_darurat ?? '',
        ];
    }
}
