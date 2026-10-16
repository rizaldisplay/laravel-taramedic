<?php

namespace App\Http\Resources;

use App\Models\DrugPrice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DrugPrice  (kontrak frontend: TarifObatItem). Relasi `product` harus sudah dimuat. */
class TarifObatResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'obatId' => (int) $this->drug_product_id,
            'obatNama' => $this->product->name,
            'obatKode' => $this->product->kode_lokal,
            'penjamin' => $this->penjamin->value,
            'satuan' => $this->satuan,
            'tarif' => (int) $this->tarif,
            'berlakuMulai' => substr((string) $this->berlaku_mulai, 0, 10),
            'berlakuSampai' => $this->berlaku_sampai ? substr((string) $this->berlaku_sampai, 0, 10) : null,
            'sumber' => $this->sumber,
            'catatan' => $this->catatan,
            'status' => $this->is_active ? 'Aktif' : 'Nonaktif',
        ];
    }
}
