<?php

namespace App\Http\Resources\ProdukObat;

use App\Models\DrugProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DrugProduct  (kontrak frontend: ProdukObatItem) */
class ProdukObatResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kfaCode' => $this->kfa_code,
            'kodeLokal' => $this->kode_lokal,
            'nama' => $this->name,
            'namaGenerik' => $this->generic_name,
            'namaDagang' => $this->brand_name,
            'zatAktif' => $this->active_ingredient,
            'kekuatan' => $this->strength,
            'satuan' => $this->strength_unit,
            'bentukSediaan' => $this->dosage_form,
            'rute' => $this->routes ?? [],
            'produsen' => $this->manufacturer,
            'nomorRegistrasi' => $this->registration_number,
            'statusFormularium' => $this->formulary_status->value,
            'status' => $this->is_active ? 'Aktif' : 'Nonaktif',
            'sumber' => $this->source,
            'sinkronTerakhir' => $this->synced_at?->toIso8601String(),
        ];
    }
}
