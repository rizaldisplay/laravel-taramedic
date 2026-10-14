<?php

namespace App\Http\Resources\Lokasi;

use App\Models\ServiceLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ServiceLocation  (kontrak frontend: LokasiItem) */
class LokasiPelayananResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode' => (string) $this->kode,
            'nama' => $this->name,
            'tipe' => $this->tipe->value,
            'indukId' => $this->induk_id ? (string) $this->induk_id : null,
            'indukNama' => $this->relationLoaded('induk') ? $this->induk?->name : null,
            'poliId' => $this->poli_id ? (string) $this->poli_id : null,
            'poliNama' => $this->relationLoaded('poli') ? $this->poli?->name : null,
            'fasyankesId' => $this->facility_id ? (string) $this->facility_id : null,
            'fasyankesNama' => $this->relationLoaded('facility') ? $this->facility?->name : null,
            'deskripsi' => $this->deskripsi,
            'status' => $this->is_active ? 'Aktif' : 'Nonaktif',
        ];
    }
}
