<?php

namespace App\Http\Requests\Sdmk;

use App\Enums\SdmkReferenceType as T;
use App\Http\Requests\Concerns\ChecksReference;

class IntegrasiRequest extends SdmkRequest
{
    use ChecksReference;

    public function rules(): array
    {
        return [
            'mappingSpesialisasi' => ['nullable', $this->refRule(T::MappingSpesialisasi)],
            'mappingTipePraktisi' => ['nullable', $this->refRule(T::TipePraktisi)],
        ];
    }

    public function attributes(): array
    {
        return ['mappingSpesialisasi' => 'Mapping spesialisasi', 'mappingTipePraktisi' => 'Tipe praktisi'];
    }
}

