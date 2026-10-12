<?php

namespace App\Http\Requests\Sdmk;

class FotoRequest extends SdmkRequest
{
    public function rules(): array
    {
        return ['foto' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:' . config('sdmk.max_file_kb')]];
    }

    public function attributes(): array
    {
        return ['foto' => 'Foto'];
    }
}
