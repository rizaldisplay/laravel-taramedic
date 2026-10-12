<?php

namespace App\Http\Requests\Sdmk;

class CatatanRequest extends SdmkRequest
{
    public function rules(): array
    {
        return ['catatan' => ['nullable', 'string', 'max:2000']];
    }

    public function attributes(): array
    {
        return ['catatan' => 'Catatan'];
    }
}
