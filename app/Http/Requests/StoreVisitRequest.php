<?php

namespace App\Http\Requests;

use App\DTOs\Registrasi\VisitData;
use App\Enums\CaraMasuk;
use App\Enums\JenisLayanan;
use App\Enums\JenisPembayaran;
use App\Enums\Patient\Penjamin;
use App\Enums\Patient\Prioritas;
use App\Enums\Masterdata\Loket\TipeLoket;
use App\Models\Doctor;
use App\Models\Visit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// MENGGANTIKAN StoreVisitRequest.php: validasi dokter memakai Doctor::available().
/** Kunci input camelCase sesuai payload frontend (lihat visitSlice.registerVisit). */
class StoreVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Visit::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'patientId' => ['required', 'integer', Rule::exists('patients', 'id')],
            'poliId' => ['required', 'integer', Rule::exists('polis', 'id')->where('is_active', true)],
            // Dokter harus: aktif, bertugas di poli terpilih, dan (bila terhubung ke SDMK) berizin & ditempatkan.
            'dokterId' => ['required', 'integer', function (string $attribute, mixed $value, \Closure $fail) {
                $ok = Doctor::query()->available()
                    ->whereKey($value)
                    ->where('doctors.poli_id', $this->input('poliId'))
                    ->exists();

                if (! $ok) {
                    $fail(':attribute tidak tersedia di poli ini (nonaktif, izin berakhir, atau tidak ditempatkan).');
                }
            }],
            'jenisLayanan' => ['required', Rule::enum(JenisLayanan::class)],
            'caraMasuk' => ['required', Rule::enum(CaraMasuk::class)],
            'penjamin' => ['required', Rule::enum(Penjamin::class)],
            'jenisPembayaran' => ['required', Rule::enum(JenisPembayaran::class)],
            'prioritas' => ['required', Rule::enum(Prioritas::class)],
            // Loket harus aktif dan bertipe Ruang Pendaftaran (master Loket / Counter).
            'loketId' => ['required', 'integer', Rule::exists('counters', 'id')->where('is_active', true)->where('tipe', TipeLoket::Pendaftaran->value)],
            'lokasiId' => ['required', 'integer', Rule::exists('service_locations', 'id')->where('is_active', true)->whereNotIn('tipe', ['Gedung', 'Lantai'])],
            'tiketId' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function attributes(): array
    {
        return [
            'patientId' => 'Pasien',
            'poliId' => 'Poli/unit tujuan',
            'dokterId' => 'Dokter / nakes',
            'jenisLayanan' => 'Jenis layanan',
            'caraMasuk' => 'Cara masuk',
            'penjamin' => 'Penjamin',
            'jenisPembayaran' => 'Jenis pembayaran',
            'prioritas' => 'Prioritas',
            'loketId' => 'Loket pendaftaran',
            'lokasiId' => 'Lokasi pelayanan',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib dipilih.',
            'exists' => ':attribute tidak valid.',
            'in' => ':attribute tidak valid.',
            'enum' => ':attribute tidak valid.',
        ];
    }

    public function toData(): VisitData
    {
        return VisitData::fromArray($this->validated());
    }
}