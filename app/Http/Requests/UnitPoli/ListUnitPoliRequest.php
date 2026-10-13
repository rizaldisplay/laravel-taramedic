<?php

namespace App\Http\Requests\UnitPoli;

use App\Enums\Masterdata\UnitPoli\JenisUnit;
use App\Enums\Masterdata\UnitPoli\StatusUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListUnitPoliRequest extends FormRequest
{
    // public function authorize(): bool
    // {
    //     return true;
    // }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(StatusUnit::class)],
            'jenis' => ['nullable', Rule::enum(JenisUnit::class)],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function pageNumber(): int
    {
        return (int) ($this->validated('page') ?? 1);
    }

    public function perPage(): int
    {
        return (int) ($this->validated('perPage') ?? 10);
    }
}
