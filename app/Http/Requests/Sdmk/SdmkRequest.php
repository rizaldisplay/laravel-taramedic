<?php

namespace App\Http\Requests\Sdmk;

use Illuminate\Foundation\Http\FormRequest;

/** Dasar semua request SDMK: pesan validasi Bahasa Indonesia. Otorisasi lewat Policy di controller. */
abstract class SdmkRequest extends FormRequest
{
    protected const REGEX_HP = '/^(\+62|62|0)8\d{8,12}$/';
    protected const REGEX_JAM = '/^([01]\d|2[0-3]):[0-5]\d$/';

    public function authorize(): bool
    {
        return true;
    }

    /** Nilai string kosong dianggap null (frontend mengirim null, tetapi multipart mengirim ""). */
    protected function prepareForValidation(): void
    {
        $clean = [];
        foreach ($this->all() as $k => $v) {
            if (is_string($v)) {
                $clean[$k] = trim($v) === '' ? null : trim($v);
            }
        }
        $this->merge($clean);
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'required_if' => ':attribute wajib diisi.',
            'digits' => ':attribute harus :digits digit angka.',
            'digits_between' => ':attribute harus berupa angka maksimal 3 digit.',
            'regex' => 'Format :attribute tidak valid.',
            'email' => 'Format :attribute tidak valid.',
            'max' => ':attribute terlalu panjang (maksimal :max karakter).',
            'in' => ':attribute tidak valid.',
            'date_format' => 'Format :attribute harus YYYY-MM-DD.',
            'before_or_equal' => ':attribute tidak boleh di masa depan.',
            'after_or_equal' => ':attribute tidak boleh sebelum tanggal awal.',
            'after' => ':attribute harus setelah tanggal/jam awal.',
            'integer' => ':attribute harus berupa angka bulat.',
            'min' => ':attribute minimal :min.',
            'file' => ':attribute harus berupa berkas.',
            'mimes' => ':attribute harus berformat :values.',
            'max.file' => ':attribute maksimal :max KB.',
            'exists' => ':attribute tidak ditemukan.',
            'array' => ':attribute tidak valid.',
        ];
    }
}
