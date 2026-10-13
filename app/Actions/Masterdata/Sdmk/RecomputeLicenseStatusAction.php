<?php

namespace App\Actions\Masterdata\Sdmk;

use App\Actions\Doctor\SyncDoctorsForWorkerAction;
use App\Models\HealthWorker;
use App\Support\LicenseStatus;

// MENGGANTIKAN versi paket SDMK: setelah status izin berubah, baris doctors ikut disinkronkan.
final class RecomputeLicenseStatusAction
{
    public function __construct(private readonly SyncDoctorsForWorkerAction $syncDoctors)
    {
    }

    /** Hitung ulang HealthWorker.license_status dari STR + SIP; mengembalikan true bila berubah. */
    public function execute(HealthWorker $worker): bool
    {
        $worker->unsetRelation('sips')->load('sips');

        $new = LicenseStatus::for($worker);

        if ($worker->license_status === $new) {
            return false;
        }

        // saveQuietly tidak memicu observer, jadi sinkronisasi dipanggil eksplisit.
        $worker->forceFill(['license_status' => $new])->saveQuietly();
        $this->syncDoctors->execute($worker);

        return true;
    }
}
