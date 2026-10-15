<?php

namespace App\Http\Requests\ProdukObat;

use App\Enums\Masterdata\ProdukObat\StatusProduk;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeProdukStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(StatusProduk::class)]];
    }

    public function messages(): array
    {
        return ['status.required' => 'Status wajib diisi.', 'status.enum' => 'Status tidak valid.'];
    }

    public function status(): StatusProduk
    {
        return StatusProduk::from($this->validated('status'));
    }
}
