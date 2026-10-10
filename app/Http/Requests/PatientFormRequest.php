<?php

namespace App\Http\Requests;

use App\DTOs\Registrasi\PatientData;
use App\Enums\Patient\JenisIdentitas;
use App\Enums\Patient\JenisKelamin;
use App\Enums\Patient\RegionLevel;
use App\Services\RegionService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Dasar validasi form data pasien (pendaftaran baru & ubah data).
 * Kunci input memakai camelCase persis seperti payload frontend,
 * sehingga pesan error 422 langsung cocok dengan field di form.
 */
abstract class PatientFormRequest extends FormRequest
{
    private const REGEX_HP = '/^(\+62|62|0)8\d{8,12}$/';

    /** ID pasien yang sedang diubah (null saat pendaftaran baru); NIK/paspornya sendiri tidak dianggap duplikat. */
    protected function patientId(): ?int
    {
        return null;
    }

    protected function prepareForValidation(): void
    {
        // Nomor paspor disimpan huruf besar agar pengecekan unik tidak peka huruf.
        if ($this->input('jenisIdentitas') === JenisIdentitas::Wna->value && is_string($this->input('nik'))) {
            $this->merge(['nik' => strtoupper(trim($this->input('nik')))]);
        }
    }

    public function rules(): array
    {
        $jenis = JenisIdentitas::tryFrom((string) $this->input('jenisIdentitas'));

        return [
            'jenisIdentitas' => ['required', Rule::enum(JenisIdentitas::class)],
            'nik' => match ($jenis) {
                JenisIdentitas::Nik => ['required', 'digits:16', Rule::unique('patients', 'nik')->ignore($this->patientId())],
                JenisIdentitas::Wna => ['required', 'regex:/^[A-Z0-9]{5,20}$/', Rule::unique('patients', 'nomor_paspor')->ignore($this->patientId())],
                default => ['nullable'],
            },
            'namaLengkap' => ['required', 'string', 'max:255'],
            'tempatLahir' => ['nullable', 'string', 'max:100'],
            'tanggalLahir' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:1900-01-01'],
            'jenisKelamin' => ['required', Rule::enum(JenisKelamin::class)],

            'alamatLengkap' => ['required', 'string', 'max:500'],
            // Tiap level dicek ke Wilayah.id (via cache): kodenya harus ada
            // dan benar-benar anak dari level di atasnya.
            'provinsi' => ['required', $this->regionRule(RegionLevel::Province)],
            'kabupatenKota' => ['required', $this->regionRule(RegionLevel::Regency, 'provinsi')],
            'kecamatan' => ['required', $this->regionRule(RegionLevel::District, 'kabupatenKota')],
            'desaKelurahan' => ['required', $this->regionRule(RegionLevel::Village, 'kecamatan')],
            'rt' => ['nullable', 'digits_between:1,3'],
            'rw' => ['nullable', 'digits_between:1,3'],
            'kodePos' => ['nullable', 'digits:5'],

            'nomorHP' => ['required', 'regex:' . self::REGEX_HP],
            'email' => ['nullable', 'email', 'max:255'],

            'nomorKK' => ['nullable', 'digits:16'],
            'statusPerkawinan' => ['nullable', Rule::in(['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'])],
            'kewarganegaraan' => ['required', Rule::in(['WNI', 'WNA'])],
            'bahasa' => ['nullable', 'string', 'max:50'],

            'namaKontakDarurat' => ['nullable', 'string', 'max:255'],
            'hubunganKontakDarurat' => ['nullable', 'string', 'max:50'],
            'nomorHPKontakDarurat' => ['nullable', 'regex:' . self::REGEX_HP],

            'tiketId' => ['nullable', 'string', 'max:50'],
        ];
    }

    /** Rule closure: kode wilayah harus ada di bawah kode induk yang dikirim pada field $parentField. */
    private function regionRule(RegionLevel $level, ?string $parentField = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($level, $parentField) {
            $parent = $parentField ? $this->input($parentField) : null;

            $valid = is_string($value)
                && ($parentField === null || is_string($parent))
                && app(RegionService::class)->has($level, $value, $parent);

            if (! $valid) {
                $fail(':attribute tidak valid atau tidak sesuai wilayah di atasnya.');
            }
        };
    }

    public function attributes(): array
    {
        return [
            'jenisIdentitas' => 'Jenis identitas',
            'nik' => $this->input('jenisIdentitas') === JenisIdentitas::Wna->value ? 'Nomor paspor' : 'NIK',
            'namaLengkap' => 'Nama lengkap',
            'tempatLahir' => 'Tempat lahir',
            'tanggalLahir' => 'Tanggal lahir',
            'jenisKelamin' => 'Jenis kelamin',
            'alamatLengkap' => 'Alamat lengkap',
            'provinsi' => 'Provinsi',
            'kabupatenKota' => 'Kabupaten/kota',
            'kecamatan' => 'Kecamatan',
            'desaKelurahan' => 'Desa/kelurahan',
            'rt' => 'RT',
            'rw' => 'RW',
            'kodePos' => 'Kode pos',
            'nomorHP' => 'Nomor HP',
            'email' => 'Email',
            'nomorKK' => 'Nomor KK',
            'statusPerkawinan' => 'Status perkawinan',
            'kewarganegaraan' => 'Kewarganegaraan',
            'bahasa' => 'Bahasa',
            'namaKontakDarurat' => 'Nama kontak darurat',
            'hubunganKontakDarurat' => 'Hubungan kontak darurat',
            'nomorHPKontakDarurat' => 'Nomor HP kontak darurat',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'digits' => ':attribute harus :digits digit angka.',
            'digits_between' => ':attribute harus berupa angka maksimal 3 digit.',
            'regex' => 'Format :attribute tidak valid.',
            'email' => 'Format :attribute tidak valid.',
            'max' => ':attribute terlalu panjang (maksimal :max karakter).',
            'unique' => ':attribute sudah terdaftar sebagai pasien.',
            'in' => ':attribute tidak valid.',
            'date_format' => 'Format :attribute harus YYYY-MM-DD.',
            'before_or_equal' => ':attribute tidak boleh di masa depan.',
            'after_or_equal' => ':attribute tidak valid.',
        ];
    }

    public function toData(): PatientData
    {
        return PatientData::fromArray($this->validated());
    }
}
