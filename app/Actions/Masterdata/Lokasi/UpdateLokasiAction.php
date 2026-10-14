<?php

namespace App\Actions\Masterdata\Lokasi;

use App\DTOs\Masterdata\LokasiData;
use App\Events\LokasiPelayananChanged;
use App\Exceptions\LokasiConflictException;
use App\Models\ServiceLocation;
use Illuminate\Support\Facades\DB;

final class UpdateLokasiAction
{
    public function __construct(
        private readonly ResolveFacilityAction $resolveFacility,
        private readonly EnsureStatusChangeAllowedAction $ensureStatus,
    ) {
    }

    public function execute(ServiceLocation $lokasi, LokasiData $data, ?int $userId): ServiceLocation
    {
        $descendants = $lokasi->descendantIds();

        // Induk tidak boleh dirinya sendiri atau turunannya (akan membentuk siklus).
        if ($data->indukId !== null && ($data->indukId === $lokasi->id || in_array($data->indukId, $descendants, true))) {
            throw new LokasiConflictException('indukId', 'Induk tidak boleh lokasi itu sendiri atau turunannya.');
        }

        $this->ensureStatus->execute($lokasi, $data->indukId, $data->status);
        $facilityId = $this->resolveFacility->execute($data->fasyankesId, $data->indukId, $data->poliId);

        DB::transaction(function () use ($lokasi, $data, $facilityId, $descendants) {
            $lokasi->update([
                'kode' => $data->kode,
                'name' => $data->nama,
                'tipe' => $data->tipe,
                'induk_id' => $data->indukId,
                'facility_id' => $facilityId,
                'poli_id' => $data->poliId,
                'deskripsi' => $data->deskripsi,
                'is_active' => $data->status->isActive(),
            ]);

            // Seluruh pohon berada di satu fasyankes: turunkan ke semua turunan bila fasyankes lokasi ini terisi.
            if ($facilityId && $lokasi->wasChanged('facility_id') && $descendants) {
                ServiceLocation::whereIn('id', $descendants)->update(['facility_id' => $facilityId]);
            }
        });

        LokasiPelayananChanged::dispatch($lokasi, 'diubah', $userId);

        return $lokasi;
    }
}
