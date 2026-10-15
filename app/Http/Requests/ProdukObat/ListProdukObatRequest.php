<?php

namespace App\Http\Requests\ProdukObat;

use App\Enums\Masterdata\ProdukObat\StatusProduk;
use App\Services\ProdukObat\ProdukObatService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListProdukObatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(StatusProduk::class)],
            'limit' => ['nullable', 'integer', 'min:1', 'max:' . ProdukObatService::MAX_ROWS],
        ];
    }

    public function limit(): int
    {
        return (int) ($this->validated('limit') ?? ProdukObatService::MAX_ROWS);
    }
}
