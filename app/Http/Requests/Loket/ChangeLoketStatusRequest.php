<?php

namespace App\Http\Requests\Loket;

use App\Enums\Masterdata\Loket\StatusLoket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeLoketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(StatusLoket::class)]];
    }

    public function messages(): array
    {
        return ['status.required' => 'Status wajib diisi.', 'status.enum' => 'Status tidak valid.'];
    }

    public function status(): StatusLoket
    {
        return StatusLoket::from($this->validated('status'));
    }
}
