<?php

namespace App\Http\Requests\Loket;

use App\Enums\Masterdata\Loket\StatusLoket;
use App\Enums\Masterdata\Loket\TipeLoket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListLoketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(StatusLoket::class)],
            'tipe' => ['nullable', Rule::enum(TipeLoket::class)],
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
