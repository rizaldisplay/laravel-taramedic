<?php

namespace App\Http\Resources\UnitPoli;

use App\Models\Poli;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Poli  (kontrak frontend: UnitPoliItem) */
class UnitPoliResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode' => (string) $this->kode,
            'nama' => $this->name,
            'jenis' => $this->jenis->value,
            'fasyankesId' => $this->facility_id ? (string) $this->facility_id : null,
            'fasyankesNama' => $this->relationLoaded('facility') ? $this->facility?->name : null,
            'deskripsi' => $this->deskripsi,
            'untukAntrean' => $this->untuk_antrean,
            'status' => $this->is_active ? 'Aktif' : 'Nonaktif',
        ];
    }
}
