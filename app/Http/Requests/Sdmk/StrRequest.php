<?php

namespace App\Http\Requests\Sdmk;

use App\Enums\SdmkReferenceType as T;
use App\Http\Requests\Concerns\ChecksReference;

class StrRequest extends SdmkRequest
{
    use ChecksReference;

    public function rules(): array
    {
        return [
            'nomorStr' => ['required', 'string', 'max:60'],
            'tanggalTerbit' => ['required', 'date_format:Y-m-d'],
            'berlakuHingga' => ['required', 'date_format:Y-m-d', 'after:tanggalTerbit'],
            'organisasiPenerbit' => ['required', $this->refRule(T::OrganisasiStr)],
            'sertifikatLain' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return ['nomorStr' => 'Nomor STR', 'tanggalTerbit' => 'Tanggal terbit', 'berlakuHingga' => 'Berlaku hingga',
            'organisasiPenerbit' => 'Organisasi penerbit', 'sertifikatLain' => 'Sertifikat lain'];
    }

    /** @return array<string, mixed> */
    public function attrs(): array
    {
        $v = $this->validated();

        return ['str_number' => $v['nomorStr'], 'str_issued_at' => $v['tanggalTerbit'],
            'str_expires_at' => $v['berlakuHingga'], 'str_issuer' => $v['organisasiPenerbit']];
    }
}
