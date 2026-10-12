<?php

namespace App\Http\Requests\Sdmk;

use App\Enums\StatusSdmk;
use Illuminate\Validation\Rule;

class ListSdmkRequest extends SdmkRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(StatusSdmk::class)],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function perPage(): int
    {
        return (int) ($this->validated('perPage') ?? 10);
    }

    public function pageNumber(): int
    {
        return (int) ($this->validated('page') ?? 1);
    }
}
