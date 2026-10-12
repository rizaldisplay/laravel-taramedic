<?php

namespace App\Support;

use App\Enums\StatusIzin;
use App\Models\HealthWorker;
use Carbon\CarbonInterface;

final class LicenseStatus
{
    /**
     * Status izin praktik gabungan STR + SIP.
     * Masa berlaku efektif = yang lebih awal antara STR dan SIP efektif terakhir.
     *  - tanpa STR / STR lewat / tanpa SIP efektif -> Tidak Aktif
     *  - sisa masa <= config sdmk.expiring_days     -> Akan Berakhir
     *  - selain itu                                -> Aktif
     */
    public static function for(HealthWorker $worker, ?CarbonInterface $today = null): StatusIzin
    {
        $today = ($today ?? now(config('sdmk.timezone')))->startOfDay();

        if (! $worker->str_expires_at || $worker->str_expires_at->lt($today)) {
            return StatusIzin::TidakAktif;
        }

        $sipEnd = $worker->sips
            ->filter(fn ($sip) => $sip->status !== 'Tidak Aktif' && $sip->berlaku_hingga->gte($today))
            ->max('berlaku_hingga');

        if (! $sipEnd) {
            return StatusIzin::TidakAktif;
        }

        $expiry = $worker->str_expires_at->lt($sipEnd) ? $worker->str_expires_at : $sipEnd;

        return $expiry->lte($today->copy()->addDays((int) config('sdmk.expiring_days', 30)))
            ? StatusIzin::AkanBerakhir
            : StatusIzin::Aktif;
    }
}
