<?php

namespace App\Http\Requests\Sdmk;

use App\DTOs\Masterdata\HealthWorkerData;
use App\Enums\Patient\JenisKelamin;
use App\Http\Requests\Concerns\ChecksRegion;
use Illuminate\Validation\Rule;

/** POST /sdmk (baru) dan PUT /sdmk/{sdmk}/identitas (ubah). Kunci input = payload frontend. */
class IdentitasRequest extends SdmkRequest
{
    use ChecksRegion;

    public function rules(): array
    {
        $id = $this->route('sdmk')?->id;

        return [
            'nik' => ['required', 'digits:16', Rule::unique('health_workers', 'nik')->ignore($id)],
            'namaLengkap' => ['required', 'string', 'max:255'],
            'gelarDepan' => ['nullable', 'string', 'max:30'],
            'gelarBelakang' => ['nullable', 'string', 'max:60'],
            'tempatLahir' => ['nullable', 'string', 'max:100'],
            'tanggalLahir' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:1900-01-01'],
            'jenisKelamin' => ['required', Rule::enum(JenisKelamin::class)],
            'kewarganegaraan' => ['required', Rule::in(['WNI', 'WNA'])],
            'nomorHP' => ['required', 'regex:' . self::REGEX_HP],
            'email' => ['nullable', 'email', 'max:255'],
            'alamatLengkap' => ['required', 'string', 'max:500'],
            ...$this->regionRules('provinsi', 'kabupatenKota', 'kecamatan', 'desaKelurahan'),
            'rt' => ['nullable', 'digits_between:1,3'],
            'rw' => ['nullable', 'digits_between:1,3'],
            'kodePos' => ['nullable', 'digits:5'],
            'namaKontakDarurat' => ['required', 'string', 'max:255'],
            'hubunganKontakDarurat' => ['required', Rule::in(config('sdmk.hubungan'))],
            'nomorHPKontakDarurat' => ['required', 'regex:' . self::REGEX_HP],
        ];
    }

    public function attributes(): array
    {
        return [
            'nik' => 'NIK', 'namaLengkap' => 'Nama lengkap', 'gelarDepan' => 'Gelar depan', 'gelarBelakang' => 'Gelar belakang',
            'tempatLahir' => 'Tempat lahir', 'tanggalLahir' => 'Tanggal lahir', 'jenisKelamin' => 'Jenis kelamin',
            'kewarganegaraan' => 'Kewarganegaraan', 'nomorHP' => 'Nomor HP', 'email' => 'Email',
            'alamatLengkap' => 'Alamat lengkap', 'provinsi' => 'Provinsi', 'kabupatenKota' => 'Kabupaten/kota',
            'kecamatan' => 'Kecamatan', 'desaKelurahan' => 'Desa/kelurahan', 'rt' => 'RT', 'rw' => 'RW', 'kodePos' => 'Kode pos',
            'namaKontakDarurat' => 'Nama kontak darurat', 'hubunganKontakDarurat' => 'Hubungan kontak darurat',
            'nomorHPKontakDarurat' => 'Nomor HP kontak darurat',
        ];
    }

    public function messages(): array
    {
        return [...parent::messages(), 'unique' => ':attribute sudah terdaftar sebagai SDMK.'];
    }

    public function toData(): HealthWorkerData
    {
        return HealthWorkerData::fromArray($this->validated());
    }
}
