<?php

namespace App\Http\Resources;

use App\Models\DrugPriceHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DrugPriceHistory  (kontrak frontend: RiwayatTarifItem) */
class RiwayatTarifResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'aksi' => $this->aksi,
            'oleh' => $this->user_name,
            'waktu' => $this->created_at?->toIso8601String(),
            'perubahan' => $this->perubahan ?? [],
        ];
    }
}
