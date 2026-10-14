<?php

namespace App\Actions\Masterdata\Lokasi;

use App\DTOs\Masterdata\LokasiData;
use App\Events\LokasiPelayananChanged;
use App\Exceptions\LokasiConflictException;
use App\Models\ServiceLocation;

final class CreateLokasiAction
{
    public function __construct(private readonly ResolveFacilityAction $resolveFacility)
    {
    }

    public function execute(LokasiData $data, ?int $userId): ServiceLocation
    {
        $facilityId = $this->resolveFacility->execute($data->fasyankesId, $data->indukId, $data->poliId);

        // Lokasi baru berstatus Aktif tidak boleh berinduk nonaktif.
        if ($data->status->isActive() && $data->indukId && ! ServiceLocation::whereKey($data->indukId)->where('is_active', true)->exists()) {
            throw new LokasiConflictException('indukId', 'Induk lokasi sedang nonaktif.');
        }

        $lokasi = ServiceLocation::create([
            'kode' => $data->kode,
            'name' => $data->nama,
            'tipe' => $data->tipe,
            'induk_id' => $data->indukId,
            'facility_id' => $facilityId,
            'poli_id' => $data->poliId,
            'deskripsi' => $data->deskripsi,
            'is_active' => $data->status->isActive(),
        ]);

        LokasiPelayananChanged::dispatch($lokasi, 'dibuat', $userId);

        return $lokasi;
    }
}
