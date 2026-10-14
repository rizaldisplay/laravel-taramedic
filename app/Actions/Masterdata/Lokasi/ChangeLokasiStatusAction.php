<?php

namespace App\Actions\Masterdata\Lokasi;

use App\Enums\Masterdata\Lokasi\StatusLokasi;
use App\Events\LokasiPelayananChanged;
use App\Models\ServiceLocation;

final class ChangeLokasiStatusAction
{
    public function __construct(private readonly EnsureStatusChangeAllowedAction $ensureStatus)
    {
    }

    public function execute(ServiceLocation $lokasi, StatusLokasi $status, ?int $userId): ServiceLocation
    {
        if ($lokasi->is_active === $status->isActive()) {
            return $lokasi; // idempoten
        }

        $this->ensureStatus->execute($lokasi, $lokasi->induk_id, $status);

        $lokasi->update(['is_active' => $status->isActive()]);

        LokasiPelayananChanged::dispatch($lokasi, $status->isActive() ? 'diaktifkan' : 'dinonaktifkan', $userId);

        return $lokasi;
    }
}
