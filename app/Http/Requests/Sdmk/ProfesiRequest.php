<?php

namespace App\Http\Requests\Sdmk;

use App\Enums\SdmkReferenceType as T;
use App\Enums\StatusSdmk;
use App\Http\Requests\Concerns\ChecksReference;
use Illuminate\Validation\Rule;

class ProfesiRequest extends SdmkRequest
{
    use ChecksReference;

    public function rules(): array
    {
        return [
            'jenisSdmk' => ['required', $this->refRule(T::JenisSdmk)],
            'profesi' => ['required', $this->refRule(T::Profesi, 'jenisSdmk')],
            'kompetensiUtama' => ['required', $this->refRule(T::KompetensiUtama)],
            'spesialisasi' => ['nullable', $this->refRule(T::Spesialisasi)],
            'subSpesialisasi' => ['nullable', 'required_with:spesialisasi', $this->refRule(T::SubSpesialisasi, 'spesialisasi')],
            'tingkatPendidikan' => ['required', $this->refRule(T::TingkatPendidikan)],
            'institusi' => ['nullable', 'string', 'max:255'],
            'tahunLulus' => ['nullable', 'digits:4', 'integer', 'min:1950', 'max:' . now()->year],
            'status' => ['required', Rule::enum(StatusSdmk::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'jenisSdmk' => 'Jenis SDMK', 'profesi' => 'Profesi', 'kompetensiUtama' => 'Kompetensi utama',
            'spesialisasi' => 'Spesialisasi', 'subSpesialisasi' => 'Sub spesialisasi',
            'tingkatPendidikan' => 'Tingkat pendidikan', 'institusi' => 'Institusi', 'tahunLulus' => 'Tahun lulus', 'status' => 'Status SDMK',
        ];
    }

    /** @return array<string, mixed> */
    public function attrs(): array
    {
        $v = $this->validated();

        return [
            'jenis_sdmk' => $v['jenisSdmk'], 'profesi' => $v['profesi'], 'kompetensi_utama' => $v['kompetensiUtama'],
            'spesialisasi' => $v['spesialisasi'] ?? null, 'sub_spesialisasi' => $v['subSpesialisasi'] ?? null,
            'tingkat_pendidikan' => $v['tingkatPendidikan'], 'institusi' => $v['institusi'] ?? null,
            'tahun_lulus' => $v['tahunLulus'] ?? null, 'status' => $v['status'],
        ];
    }
}
