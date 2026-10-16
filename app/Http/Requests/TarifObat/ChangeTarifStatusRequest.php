<?php

namespace App\Http\Requests\TarifObat;

use App\Enums\Masterdata\TarifObat\StatusTarif;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeTarifStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(StatusTarif::class)]];
    }

    public function messages(): array
    {
        return ['status.required' => 'Status wajib diisi.', 'status.enum' => 'Status tidak valid.'];
    }

    public function status(): StatusTarif
    {
        return StatusTarif::from($this->validated('status'));
    }
}
