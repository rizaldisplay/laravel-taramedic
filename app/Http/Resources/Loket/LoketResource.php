<?php

namespace App\Http\Resources\Loket;

use App\Models\Counter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Counter  (kontrak frontend: LoketItem) */
class LoketResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'nama' => $this->name,
            'tipe' => $this->tipe->value,
            'deskripsi' => $this->deskripsi,
            'status' => $this->is_active ? 'Aktif' : 'Nonaktif',
        ];
    }
}
