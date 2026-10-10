<?php

namespace App\DTOs\Registrasi;

use App\Enums\Patient\JenisIdentitas;
use App\Enums\Patient\JenisKelamin;
use App\Support\PhoneNumber;

/**
 * Data pasien yang sudah tervalidasi. Kunci input memakai camelCase
 * (sesuai payload frontend), kunci kolom database memakai snake_case.
 */
final readonly class PatientData
{
    public function __construct(
        public JenisIdentitas $jenisIdentitas,
        public ?string $nomorIdentitas,
        public string $namaLengkap,
        public ?string $tempatLahir,
        public string $tanggalLahir,
        public JenisKelamin $jenisKelamin,
        public string $alamatLengkap,
        public string $provinsi,
        public string $kabupatenKota,
        public string $kecamatan,
        public string $desaKelurahan,
        public ?string $rt,
        public ?string $rw,
        public ?string $kodePos,
        public string $nomorHP,
        public ?string $email,
        public ?string $nomorKK,
        public ?string $statusPerkawinan,
        public string $kewarganegaraan,
        public ?string $bahasa,
        public ?string $namaKontakDarurat,
        public ?string $hubunganKontakDarurat,
        public ?string $nomorHPKontakDarurat,
        public ?string $ticketId = null,
    ) {
    }

    /** @param array<string, mixed> $v hasil $request->validated() */
    public static function fromArray(array $v): self
    {
        $jenis = JenisIdentitas::from($v['jenisIdentitas']);

        return new self(
            jenisIdentitas: $jenis,
            nomorIdentitas: $jenis->memerlukanNomor() ? ($v['nik'] ?? null) : null,
            namaLengkap: $v['namaLengkap'],
            tempatLahir: $v['tempatLahir'] ?? null,
            tanggalLahir: $v['tanggalLahir'],
            jenisKelamin: JenisKelamin::from($v['jenisKelamin']),
            alamatLengkap: $v['alamatLengkap'],
            provinsi: $v['provinsi'],
            kabupatenKota: $v['kabupatenKota'],
            kecamatan: $v['kecamatan'],
            desaKelurahan: $v['desaKelurahan'],
            rt: $v['rt'] ?? null,
            rw: $v['rw'] ?? null,
            kodePos: $v['kodePos'] ?? null,
            nomorHP: $v['nomorHP'],
            email: $v['email'] ?? null,
            nomorKK: $v['nomorKK'] ?? null,
            statusPerkawinan: $v['statusPerkawinan'] ?? null,
            kewarganegaraan: $v['kewarganegaraan'],
            bahasa: $v['bahasa'] ?? null,
            namaKontakDarurat: $v['namaKontakDarurat'] ?? null,
            hubunganKontakDarurat: $v['hubunganKontakDarurat'] ?? null,
            nomorHPKontakDarurat: $v['nomorHPKontakDarurat'] ?? null,
            ticketId: isset($v['tiketId']) ? (string) $v['tiketId'] : null,
        );
    }

    /** @return array<string, mixed> atribut kolom tabel patients (tanpa nomor_rm & created_by) */
    public function toAttributes(): array
    {
        return [
            'jenis_identitas' => $this->jenisIdentitas,
            'nik' => $this->jenisIdentitas === JenisIdentitas::Nik ? $this->nomorIdentitas : null,
            'nomor_paspor' => $this->jenisIdentitas === JenisIdentitas::Wna ? $this->nomorIdentitas : null,
            'nama_lengkap' => $this->namaLengkap,
            'tempat_lahir' => $this->tempatLahir,
            'tanggal_lahir' => $this->tanggalLahir,
            'jenis_kelamin' => $this->jenisKelamin,
            'alamat_lengkap' => $this->alamatLengkap,
            'province_code' => $this->provinsi,
            'regency_code' => $this->kabupatenKota,
            'district_code' => $this->kecamatan,
            'village_code' => $this->desaKelurahan,
            'rt' => $this->rt,
            'rw' => $this->rw,
            'kode_pos' => $this->kodePos,
            'nomor_hp' => PhoneNumber::normalize($this->nomorHP),
            'email' => $this->email,
            'nomor_kk' => $this->nomorKK,
            'status_perkawinan' => $this->statusPerkawinan,
            'kewarganegaraan' => $this->kewarganegaraan,
            'bahasa' => $this->bahasa,
            'nama_kontak_darurat' => $this->namaKontakDarurat,
            'hubungan_kontak_darurat' => $this->hubunganKontakDarurat,
            'nomor_hp_kontak_darurat' => PhoneNumber::normalize($this->nomorHPKontakDarurat),
        ];
    }
}
