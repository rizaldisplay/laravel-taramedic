<?php

namespace App\Http\Requests;

use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;

class SearchPatientsRequest extends FormRequest
{
    // public function authorize(): bool
    // {
    //     return $this->user()?->can('viewAny', Patient::class) ?? false;
    // }

    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'between:1,50'],
        ];
    }

    public function term(): string
    {
        return trim((string) $this->validated('query'));
    }

    public function pageNumber(): int
    {
        return (int) ($this->validated('page') ?? 1);
    }

    public function perPage(): int
    {
        return (int) ($this->validated('limit') ?? 10);
    }
}
