<?php

namespace App\DTOs\Masterdata;

use App\Enums\Patient\JenisKelamin;
use App\Support\PhoneNumber;

/** Data tab Identitas yang sudah lolos validasi. */
final readonly class HealthWorkerData
{
    public function __construct(
        public string $nik,
        public string $namaLengkap,
        public ?string $gelarDepan,
        public ?string $gelarBelakang,
        public ?string $tempatLahir,
        public string $tanggalLahir,
        public JenisKelamin $jenisKelamin,
        public string $kewarganegaraan,
        public string $nomorHP,
        public ?string $email,
        public string $alamatLengkap,
        public string $provinsi,
        public string $kabupatenKota,
        public string $kecamatan,
        public string $desaKelurahan,
        public ?string $rt,
        public ?string $rw,
        public ?string $kodePos,
        public string $namaKontakDarurat,
        public string $hubunganKontakDarurat,
        public string $nomorHPKontakDarurat,
    ) {
    }

    /** @param array<string, mixed> $v */
    public static function fromArray(array $v): self
    {
        return new self(
            nik: $v['nik'],
            namaLengkap: trim($v['namaLengkap']),
            gelarDepan: $v['gelarDepan'] ?? null,
            gelarBelakang: $v['gelarBelakang'] ?? null,
            tempatLahir: $v['tempatLahir'] ?? null,
            tanggalLahir: $v['tanggalLahir'],
            jenisKelamin: JenisKelamin::from($v['jenisKelamin']),
            kewarganegaraan: $v['kewarganegaraan'],
            nomorHP: PhoneNumber::normalize($v['nomorHP']),
            email: isset($v['email']) ? strtolower($v['email']) : null,
            alamatLengkap: $v['alamatLengkap'],
            provinsi: $v['provinsi'],
            kabupatenKota: $v['kabupatenKota'],
            kecamatan: $v['kecamatan'],
            desaKelurahan: $v['desaKelurahan'],
            rt: $v['rt'] ?? null,
            rw: $v['rw'] ?? null,
            kodePos: $v['kodePos'] ?? null,
            namaKontakDarurat: trim($v['namaKontakDarurat']),
            hubunganKontakDarurat: $v['hubunganKontakDarurat'],
            nomorHPKontakDarurat: PhoneNumber::normalize($v['nomorHPKontakDarurat']),
        );
    }

    /** @return array<string, mixed> kolom tabel health_workers (tanpa nama wilayah). */
    public function toAttributes(): array
    {
        return [
            'nik' => $this->nik,
            'nama_lengkap' => $this->namaLengkap,
            'gelar_depan' => $this->gelarDepan,
            'gelar_belakang' => $this->gelarBelakang,
            'tempat_lahir' => $this->tempatLahir,
            'tanggal_lahir' => $this->tanggalLahir,
            'jenis_kelamin' => $this->jenisKelamin,
            'kewarganegaraan' => $this->kewarganegaraan,
            'nomor_hp' => $this->nomorHP,
            'email' => $this->email,
            'alamat_lengkap' => $this->alamatLengkap,
            'province_code' => $this->provinsi,
            'regency_code' => $this->kabupatenKota,
            'district_code' => $this->kecamatan,
            'village_code' => $this->desaKelurahan,
            'rt' => $this->rt,
            'rw' => $this->rw,
            'kode_pos' => $this->kodePos,
            'nama_kontak_darurat' => $this->namaKontakDarurat,
            'hubungan_kontak_darurat' => $this->hubunganKontakDarurat,
            'nomor_hp_kontak_darurat' => $this->nomorHPKontakDarurat,
        ];
    }
}
