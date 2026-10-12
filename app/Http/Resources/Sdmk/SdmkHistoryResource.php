<?php

namespace App\Http\Resources\Sdmk;

use App\Models\WorkerActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkerActivity  (kontrak: SdmkHistoryItem) */
class SdmkHistoryResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'waktu' => $this->created_at->timezone(config('sdmk.timezone'))->format('d-m-Y H:i') . ' WIB',
            'aksi' => $this->aksi,
            'oleh' => $this->user?->name ?? 'Sistem',
            'keterangan' => $this->keterangan,
        ];
    }
}
