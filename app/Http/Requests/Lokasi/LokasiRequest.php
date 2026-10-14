<?php

namespace App\Http\Requests\Lokasi;

use App\DTOs\Masterdata\LokasiData;
use App\Enums\Masterdata\Lokasi\StatusLokasi;
use App\Enums\Masterdata\Lokasi\TipeLokasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** POST /lokasi-pelayanan dan PUT /lokasi-pelayanan/{lokasi}. Kunci input = payload frontend (camelCase). */
class LokasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // otorisasi lewat Policy di controller
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_filter([
            'kode' => is_string($this->input('kode')) ? strtoupper(trim($this->input('kode'))) : null,
            'nama' => is_string($this->input('nama')) ? trim($this->input('nama')) : null,
            'deskripsi' => is_string($this->input('deskripsi')) && trim($this->input('deskripsi')) !== '' ? trim($this->input('deskripsi')) : null,
        ], fn ($v) => $v !== null));
    }

    public function rules(): array
    {
        $id = $this->route('lokasi')?->id;

        return [
            'kode' => ['required', 'regex:/^[A-Z0-9][A-Z0-9-]{1,29}$/', Rule::unique('service_locations', 'kode')->ignore($id)],
            'nama' => ['required', 'string', 'min:2', 'max:100', Rule::unique('service_locations', 'name')->ignore($id)],
            'tipe' => ['required', Rule::enum(TipeLokasi::class)],
            'indukId' => ['nullable', 'integer', Rule::exists('service_locations', 'id')->where('is_active', true)],
            'fasyankesId' => ['nullable', 'integer', Rule::exists('facilities', 'id')->where('is_active', true)],
            // Hanya ruangan yang boleh dikaitkan ke poli.
            'poliId' => ['nullable', 'integer', 'prohibited_unless:tipe,Ruang', Rule::exists('polis', 'id')->where('is_active', true)],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(StatusLokasi::class)],
        ];
    }

    public function attributes(): array
    {
        return ['kode' => 'Kode', 'nama' => 'Nama', 'tipe' => 'Tipe', 'indukId' => 'Induk lokasi',
            'fasyankesId' => 'Fasyankes', 'poliId' => 'Unit/poli', 'deskripsi' => 'Deskripsi', 'status' => 'Status'];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'regex' => ':attribute hanya boleh huruf besar, angka, dan tanda hubung (2-30 karakter).',
            'unique' => ':attribute sudah digunakan.',
            'min' => ':attribute minimal :min karakter.',
            'max' => ':attribute maksimal :max karakter.',
            'in' => ':attribute tidak valid.',
            'exists' => ':attribute tidak ditemukan atau tidak aktif.',
            'integer' => ':attribute tidak valid.',
            'prohibited_unless' => ':attribute hanya boleh diisi untuk tipe Ruang.',
        ];
    }

    public function toData(): LokasiData
    {
        $data = $this->validated();
        // Tanpa field status: pembuatan = Aktif; pengubahan = pertahankan status sekarang.
        $data['status'] ??= $this->route('lokasi')?->is_active === false ? 'Nonaktif' : 'Aktif';

        return LokasiData::fromArray($data);
    }
}
