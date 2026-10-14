<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk response = type `VisitResult` di frontend. Eager load: poli, doctor.healthWorker, location
 *
 * @mixin \App\Models\Visit
 */
class VisitResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nomorKunjungan' => $this->nomor_kunjungan,
            'tanggal' => $this->registered_at->format('d-m-Y H:i'),
            'poli' => $this->poli->name,
            'dokter' => $this->doctor->displayName(),
            'lokasi' => $this->location->name,
        ];
    }
}

