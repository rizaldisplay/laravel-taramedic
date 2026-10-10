<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk response = type `Wilayah` di frontend: { kode, nama }.
 * Koleksi dibungkus { data: [...] }, sudah ditangani thunk fetchWilayah.
 * @mixin \App\DTOs\RegionData
 */
class RegionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'kode' => $this->code,
            'nama' => $this->name,
        ];
    }
}
