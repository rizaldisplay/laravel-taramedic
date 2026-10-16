<?php

namespace App\Http\Requests\TarifObat;

use App\Enums\Patient\Penjamin;
use App\Enums\Masterdata\TarifObat\StatusTarif;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTarifObatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'penjamin' => ['nullable', Rule::in(Penjamin::values())],
            'status' => ['nullable', Rule::enum(StatusTarif::class)],
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
