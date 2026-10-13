<?php

namespace App\Http\Requests\UnitPoli;

use App\Enums\Masterdata\UnitPoli\StatusUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeStatusRequest extends FormRequest
{
    // public function authorize(): bool
    // {
    //     return true;
    // }

    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(StatusUnit::class)]];
    }

    public function messages(): array
    {
        return ['status.required' => 'Status wajib diisi.', 'status.enum' => 'Status tidak valid.'];
    }

    public function status(): StatusUnit
    {
        return StatusUnit::from($this->validated('status'));
    }
}
