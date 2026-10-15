<?php

namespace App\Http\Requests\Loket;

use App\DTOs\Masterdata\LoketData;
use App\Enums\Masterdata\Loket\StatusLoket;
use App\Enums\Masterdata\Loket\TipeLoket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** POST /loket-counter dan PUT /loket-counter/{loket}. Kunci input = payload frontend (camelCase). */
class LoketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // otorisasi lewat Policy di controller
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_filter([
            'code' => is_string($this->input('code')) ? strtoupper(trim($this->input('code'))) : null,
            'nama' => is_string($this->input('nama')) ? trim($this->input('nama')) : null,
            'deskripsi' => is_string($this->input('deskripsi')) && trim($this->input('deskripsi')) !== '' ? trim($this->input('deskripsi')) : null,
        ], fn ($v) => $v !== null));
    }

    public function rules(): array
    {
        $id = $this->route('loket')?->id;

        return [
            'code' => ['required', 'regex:/^[A-Z0-9][A-Z0-9-]{1,29}$/', Rule::unique('counters', 'code')->ignore($id)],
            'nama' => ['required', 'string', 'min:2', 'max:100', Rule::unique('counters', 'name')->ignore($id)],
            'tipe' => ['required', Rule::enum(TipeLoket::class)],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(StatusLoket::class)],
        ];
    }

    public function attributes(): array
    {
        return ['code' => 'Code', 'nama' => 'Nama', 'tipe' => 'Tipe', 'deskripsi' => 'Deskripsi', 'status' => 'Status'];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'regex' => ':attribute hanya boleh huruf besar, angka, dan tanda hubung (2-30 karakter).',
            'unique' => ':attribute sudah digunakan.',
            'min' => ':attribute minimal :min karakter.',
            'max' => ':attribute maksimal :max karakter.',
            'enum' => ':attribute tidak valid.',
        ];
    }

    public function toData(): LoketData
    {
        $data = $this->validated();
        // Tanpa field status: pembuatan = Aktif; pengubahan = pertahankan status sekarang.
        $data['status'] ??= $this->route('loket')?->is_active === false ? 'Nonaktif' : 'Aktif';

        return LoketData::fromArray($data);
    }
}
