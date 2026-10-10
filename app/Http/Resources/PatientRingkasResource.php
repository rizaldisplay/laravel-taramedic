<?php

namespace App\Http\Resources;

use App\Support\MaskData;
use App\Support\Usia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk response = tipe HasilPencarianPasien di frontend.
 * Nama kolom model Pasien di bawah adalah ASUMSI — sesuaikan di sini saja.
 *
 * @mixin \App\Models\Patient
 */
class PatientRingkasResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $terakhir = $this->kunjunganTerakhir;

        return [
            'nomorRM' => $this->nomor_rm,
            'namaLengkap' => $this->nama_lengkap,
            // terima 'L'/'P' maupun 'Laki-laki'/'Perempuan'
            'jenisKelamin' => str_starts_with(strtoupper((string) $this->jenis_kelamin), 'L') ? 'L' : 'P',
            'tanggalLahir' => $this->tanggal_lahir?->format('d-m-Y'),
            'usiaString' => Usia::format($this->tanggal_lahir),
            'nik' => MaskData::nik($this->nik),
            'noTelepon' => MaskData::telepon($this->nomor_hp),
            'penjaminUtama' => $this->penjamin_utama,
            'alamat' => $this->alamat_lengkap,
            'status' => $terakhir ? 'Lama' : 'Baru',
            'tanggalKunjunganTerakhir' => $terakhir?->waktu_daftar?->format('d-m-Y'),
            'poliKunjunganTerakhir' => $terakhir?->poli?->nama,
            'satuSehat' => [
                'statusTerhubung' => filled($this->ihs_number),
                'ihsNumber' => $this->ihs_number,
                'nikDukcapil' => MaskData::nik($this->nik),
                'terakhirSinkron' => $this->satusehat_synced_at
                    ? $this->satusehat_synced_at->format('d-m-Y H:i').' WIB'
                    : null,
            ],
        ];
    }
}
