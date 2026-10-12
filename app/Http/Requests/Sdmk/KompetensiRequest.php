<?php

namespace App\Http\Requests\Sdmk;

class KompetensiRequest extends SdmkRequest
{
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'penyelenggara' => ['required', 'string', 'max:255'],
            'tanggalPeroleh' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'berlakuHingga' => ['nullable', 'date_format:Y-m-d', 'after:tanggalPeroleh'],
            'dokumen' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:' . config('sdmk.max_file_kb')],
        ];
    }

    public function attributes(): array
    {
        return ['nama' => 'Nama kompetensi', 'penyelenggara' => 'Penyelenggara', 'tanggalPeroleh' => 'Tanggal peroleh',
            'berlakuHingga' => 'Berlaku hingga', 'dokumen' => 'Dokumen'];
    }

    /** @return array<string, mixed> */
    public function attrs(): array
    {
        $v = $this->validated();

        return ['nama' => $v['nama'], 'penyelenggara' => $v['penyelenggara'],
            'tanggal_peroleh' => $v['tanggalPeroleh'], 'berlaku_hingga' => $v['berlakuHingga'] ?? null];
    }
}
