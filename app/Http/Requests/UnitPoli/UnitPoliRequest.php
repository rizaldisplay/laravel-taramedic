<?php

namespace App\Http\Requests\UnitPoli;

use App\DTOs\Masterdata\UnitPoliData;
use App\Enums\Masterdata\UnitPoli\JenisUnit;
use App\Enums\Masterdata\UnitPoli\StatusUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** POST /unit-poli dan PUT /unit-poli/{poli}. Kunci input = payload frontend (camelCase). */
class UnitPoliRequest extends FormRequest
{
    // public function authorize(): bool
    // {
    //     return true; // otorisasi lewat Policy di controller
    // }

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
        $id = $this->route('poli')?->id;

        return [
            'kode' => ['required', 'regex:/^[A-Z0-9][A-Z0-9-]{1,19}$/', Rule::unique('polis', 'kode')->ignore($id)],
            'nama' => ['required', 'string', 'min:2', 'max:100', Rule::unique('polis', 'name')->ignore($id)],
            'jenis' => ['required', Rule::enum(JenisUnit::class)],
            'fasyankesId' => ['nullable', Rule::exists('facilities', 'id')->where('is_active', true)],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'untukAntrean' => ['required', 'boolean'],
            'status' => ['nullable', Rule::enum(StatusUnit::class)],
        ];
    }

    public function attributes(): array
    {
        return ['kode' => 'Kode', 'nama' => 'Nama', 'jenis' => 'Jenis', 'fasyankesId' => 'Fasyankes',
            'deskripsi' => 'Deskripsi', 'untukAntrean' => 'Filter antrean', 'status' => 'Status'];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'regex' => ':attribute hanya boleh huruf besar, angka, dan tanda hubung (2-20 karakter).',
            'unique' => ':attribute sudah digunakan.',
            'min' => ':attribute minimal :min karakter.',
            'max' => ':attribute maksimal :max karakter.',
            'in' => ':attribute tidak valid.',
            'exists' => ':attribute tidak ditemukan atau tidak aktif.',
            'boolean' => ':attribute tidak valid.',
        ];
    }

    public function toData(): UnitPoliData
    {
        $data = $this->validated();
        // Tanpa field status: pembuatan = Aktif; pengubahan = pertahankan status sekarang (jangan aktifkan ulang diam-diam).
        $data['status'] ??= $this->route('poli')?->is_active === false ? 'Nonaktif' : 'Aktif';

        return UnitPoliData::fromArray($data);
    }
}
