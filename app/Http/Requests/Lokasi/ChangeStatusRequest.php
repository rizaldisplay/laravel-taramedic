<?php

namespace App\Http\Requests\Lokasi;

use App\Enums\Masterdata\Lokasi\StatusLokasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(StatusLokasi::class)]];
    }

    public function messages(): array
    {
        return ['status.required' => 'Status wajib diisi.', 'status.enum' => 'Status tidak valid.'];
    }

    public function status(): StatusLokasi
    {
        return StatusLokasi::from($this->validated('status'));
    }
}
