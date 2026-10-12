<?php

namespace App\Http\Resources\Sdmk;

use App\Models\HealthWorker;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin HealthWorker  (kontrak: SdmkListItem). Tidak memuat NIK. */
class SdmkListResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $placement = $this->relationLoaded('placements') ? $this->placements->first() : null;

        return [
            'id' => $this->id,
            'sdmkId' => $this->sdmk_code,
            'namaLengkap' => $this->displayName(),
            'profesi' => (string) $this->profesi,
            'spesialisasi' => $this->spesialisasi,
            'status' => $this->status->value,
            'statusIzin' => $this->license_status->value,
            'nomorStr' => $this->str_number,
            'tempatPraktikUtama' => $placement?->facility?->name,
            'statusIhs' => $this->ihs_status->value,
            'inisial' => collect(preg_split('/\s+/', trim($this->nama_lengkap)))
                ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode(''),
        ];
    }
}
