<?php

namespace App\Http\Requests;

use App\DTOs\Registrasi\VisitData;
use App\Enums\Patient\CaraMasuk;
use App\Enums\Patient\JenisLayanan;
use App\Enums\Patient\JenisPembayaran;
use App\Enums\Patient\Penjamin;
use App\Enums\Patient\Prioritas;
use App\Models\Visit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Kunci input camelCase sesuai payload frontend (lihat visitSlice.registerVisit). */
class StoreVisitRequest extends FormRequest
{
    // public function authorize(): bool
    // {
    //     return $this->user()?->can('create', Visit::class) ?? false;
    // }

    public function rules(): array
    {
        return [
            'patientId' => ['required', 'integer', Rule::exists('patients', 'id')],
            'poliId' => ['required', 'integer', Rule::exists('polis', 'id')->where('is_active', true)],
            // Dokter harus bertugas di poli yang dipilih
            'dokterId' => ['required', 'integer', Rule::exists('doctors', 'id')
                ->where('poli_id', $this->input('poliId'))
                ->where('is_active', true)],
            'jenisLayanan' => ['required', Rule::enum(JenisLayanan::class)],
            'caraMasuk' => ['required', Rule::enum(CaraMasuk::class)],
            'penjamin' => ['required', Rule::enum(Penjamin::class)],
            'jenisPembayaran' => ['required', Rule::enum(JenisPembayaran::class)],
            'prioritas' => ['required', Rule::enum(Prioritas::class)],
            'loketId' => ['required', 'integer', Rule::in(array_keys(config('registration.counters')))],
            'lokasiId' => ['required', 'integer', Rule::exists('service_locations', 'id')->where('is_active', true)],
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
