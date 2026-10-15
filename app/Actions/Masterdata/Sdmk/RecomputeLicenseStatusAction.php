<?php

namespace App\Actions\Masterdata\Sdmk;

use App\Actions\Doctor\SyncDoctorsForWorkerAction;
use App\Models\HealthWorker;
use App\Support\LicenseStatus;

final class RecomputeLicenseStatusAction
{
    /** Hitung ulang HealthWorker.license_status dari STR + SIP; mengembalikan true bila berubah. */
    public function execute(HealthWorker $worker): bool
    {
        $worker->unsetRelation('sips')->load('sips');

        $new = LicenseStatus::for($worker);

        if ($worker->license_status === $new) {
            return false;
        }

        $worker->forceFill(['license_status' => $new])->saveQuietly();

        return true;
    }
}
