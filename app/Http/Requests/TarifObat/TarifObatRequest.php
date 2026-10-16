<?php

namespace App\Http\Requests\TarifObat;

use App\DTOs\Masterdata\TarifObatData;
use App\Enums\Patient\Penjamin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** POST /farmasi/tarif-obat dan PUT /farmasi/tarif-obat/{tarif}. Kunci input = payload frontend (camelCase). */
class TarifObatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // otorisasi lewat Policy di controller
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if (is_string($this->input('satuan'))) {
            $merge['satuan'] = trim($this->input('satuan'));
        }
        // Kolom opsional: teks kosong -> null.
        foreach (['berlakuSampai', 'sumber', 'catatan'] as $key) {
            if (is_string($this->input($key))) {
                $merge[$key] = trim($this->input($key)) === '' ? null : trim($this->input($key));
            }
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        $edit = $this->route('tarif') !== null;

        return [
            // Tarif baru hanya untuk obat aktif; pada pengubahan obat tidak berubah (dicek di Action), jadi cukup ada.
            'obatId' => ['required', 'integer', $edit
                ? Rule::exists('drug_products', 'id')
                : Rule::exists('drug_products', 'id')->where('is_active', true)],
            'penjamin' => ['required', Rule::in(Penjamin::values())],
            'satuan' => ['required', 'string', 'min:1', 'max:30'],
            'tarif' => ['required', 'integer', 'min:0', 'max:999999999'],
            'berlakuMulai' => ['required', 'date_format:Y-m-d'],
            'berlakuSampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:berlakuMulai'],
            'sumber' => ['nullable', 'string', 'max:100'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'obatId' => 'Obat', 'penjamin' => 'Penjamin', 'satuan' => 'Satuan', 'tarif' => 'Tarif',
            'berlakuMulai' => 'Tanggal mulai', 'berlakuSampai' => 'Tanggal akhir', 'sumber' => 'Sumber tarif', 'catatan' => 'Catatan',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'obatId.exists' => 'Obat tidak ditemukan atau sudah nonaktif.',
            'penjamin.in' => ':attribute tidak valid.',
            'integer' => ':attribute harus bilangan bulat.',
            'tarif.min' => ':attribute tidak boleh negatif.',
            'tarif.max' => ':attribute maksimal Rp 999.999.999.',
            'date_format' => ':attribute tidak valid (format YYYY-MM-DD).',
            'berlakuSampai.after_or_equal' => ':attribute tidak boleh sebelum tanggal mulai.',
            'string' => ':attribute tidak valid.',
            'min' => ':attribute minimal :min karakter.',
            'max' => ':attribute maksimal :max karakter.',
        ];
    }

    public function toData(): TarifObatData
    {
        return TarifObatData::fromArray($this->validated());
    }
}
