<?php

namespace App\Http\Requests\ProdukObat;

use App\DTOs\Masterdata\ProdukObatData;
use App\Enums\Masterdata\ProdukObat\StatusFormularium;
use App\Enums\Masterdata\ProdukObat\StatusProduk;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** POST /farmasi/produk-obat dan PUT /farmasi/produk-obat/{produk}. Kunci input = payload frontend (camelCase). */
class ProdukObatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // otorisasi lewat Policy di controller
    }

    protected function prepareForValidation(): void
    {
        $teks = fn (string $key) => is_string($this->input($key)) ? trim($this->input($key)) : null;

        $merge = array_filter([
            'kfaCode' => $teks('kfaCode'),
            'kodeLokal' => is_string($this->input('kodeLokal')) ? strtoupper(trim($this->input('kodeLokal'))) : null,
            'nama' => $teks('nama'),
            'zatAktif' => $teks('zatAktif'),
            'bentukSediaan' => $teks('bentukSediaan'),
        ], fn ($v) => $v !== null);

        // Kolom opsional: teks kosong -> null.
        foreach (['namaGenerik', 'kekuatan', 'satuan', 'produsen'] as $key) {
            if (is_string($this->input($key))) {
                $merge[$key] = trim($this->input($key)) === '' ? null : trim($this->input($key));
            }
        }

        // Rute: kode huruf besar, label dirapikan. Bentuk selain larik dibiarkan agar ditolak aturan 'array'.
        if (is_array($this->input('rute'))) {
            $merge['rute'] = array_map(
                fn ($r) => is_array($r) ? [
                    'kode' => is_string($r['kode'] ?? null) ? strtoupper(trim($r['kode'])) : ($r['kode'] ?? null),
                    'label' => is_string($r['label'] ?? null) ? trim($r['label']) : ($r['label'] ?? null),
                ] : $r,
                array_values($this->input('rute')),
            );
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        $id = $this->route('produk')?->id;

        return [
            'kfaCode' => ['required', 'regex:/^[0-9A-Za-z-]{3,30}$/', Rule::unique('drug_products', 'kfa_code')->ignore($id)],
            'kodeLokal' => ['required', 'regex:/^[A-Z0-9][A-Z0-9-]{1,29}$/', Rule::unique('drug_products', 'kode_lokal')->ignore($id)],
            'nama' => ['required', 'string', 'min:2', 'max:150'],
            'namaGenerik' => ['nullable', 'string', 'max:150'],
            'zatAktif' => ['required', 'string', 'max:200'],
            // Kekuatan dan satuan diisi berpasangan.
            'kekuatan' => ['nullable', 'required_with:satuan', 'regex:/^\d+(\.\d+)?$/', 'max:12'],
            'satuan' => ['nullable', 'required_with:kekuatan', 'string', 'max:20'],
            'bentukSediaan' => ['required', 'string', 'max:50'],
            'rute' => ['required', 'array', 'min:1', 'max:10'],
            'rute.*.kode' => ['required', 'string', 'regex:/^[A-Z0-9]{1,10}$/', 'distinct'],
            'rute.*.label' => ['required', 'string', 'max:50'],
            'produsen' => ['nullable', 'string', 'max:100'],
            'statusFormularium' => ['required', Rule::enum(StatusFormularium::class)],
            'status' => ['nullable', Rule::enum(StatusProduk::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'kfaCode' => 'KFA code', 'kodeLokal' => 'Kode lokal', 'nama' => 'Nama produk', 'namaGenerik' => 'Nama generik',
            'zatAktif' => 'Zat aktif', 'kekuatan' => 'Kekuatan', 'satuan' => 'Satuan', 'bentukSediaan' => 'Bentuk sediaan',
            'rute' => 'Rute pemberian', 'rute.*.kode' => 'Kode rute', 'rute.*.label' => 'Label rute',
            'produsen' => 'Produsen', 'statusFormularium' => 'Status formularium', 'status' => 'Status',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'required_with' => ':attribute wajib diisi bila pasangannya diisi.',
            'kfaCode.regex' => ':attribute 3-30 karakter (huruf, angka, tanda hubung).',
            'kodeLokal.regex' => ':attribute hanya boleh huruf besar, angka, dan tanda hubung (2-30 karakter).',
            'kekuatan.regex' => ':attribute harus berupa angka.',
            'rute.*.kode.regex' => ':attribute 1-10 karakter huruf/angka.',
            'unique' => ':attribute sudah digunakan.',
            'distinct' => ':attribute dobel.',
            'min' => ':attribute minimal :min karakter.',
            'max' => ':attribute maksimal :max karakter.',
            'rute.min' => ':attribute minimal :min.',
            'rute.max' => ':attribute maksimal :max.',
            'array' => ':attribute tidak valid.',
            'enum' => ':attribute tidak valid.',
        ];
    }

    public function toData(): ProdukObatData
    {
        $data = $this->validated();
        // Tanpa field status: pembuatan = Aktif; pengubahan = pertahankan status sekarang.
        $data['status'] ??= $this->route('produk')?->is_active === false ? 'Nonaktif' : 'Aktif';

        return ProdukObatData::fromArray($data);
    }
}
