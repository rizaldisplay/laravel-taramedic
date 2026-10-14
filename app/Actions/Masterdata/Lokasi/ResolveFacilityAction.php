<?php

namespace App\Actions\Masterdata\Lokasi;

use App\Exceptions\LokasiConflictException;
use App\Models\Poli;
use App\Models\ServiceLocation;

final class ResolveFacilityAction
{
    /**
     * Fasyankes efektif sebuah lokasi. Seluruh pohon lokasi berada di satu fasyankes, dan ruang
     * yang dikaitkan ke poli berada di fasyankes poli tersebut:
     *  - tidak diisi -> mewarisi fasyankes induk, lalu fasyankes poli;
     *  - diisi tetapi berbeda dari fasyankes induk atau poli -> ditolak.
     */
    public function execute(?int $requestedFacilityId, ?int $indukId, ?int $poliId = null): ?int
    {
        $parentFacility = $indukId ? ServiceLocation::find($indukId)?->facility_id : null;
        $poliFacility = $poliId ? Poli::find($poliId)?->facility_id : null;

        if ($parentFacility && $requestedFacilityId && $requestedFacilityId !== (int) $parentFacility) {
            throw new LokasiConflictException('fasyankesId', 'Fasyankes harus sama dengan fasyankes induk lokasi.');
        }

        $effective = $requestedFacilityId ?? ($parentFacility ? (int) $parentFacility : null);

        if ($poliFacility) {
            if ($effective && $effective !== (int) $poliFacility) {
                throw new LokasiConflictException('poliId', 'Unit/poli berada di fasyankes yang berbeda dengan lokasi ini.');
            }
            $effective ??= (int) $poliFacility;
        }

        return $effective;
    }
}
