<?php

namespace App\Actions\Masterdata\Loket;

use App\Enums\Masterdata\Loket\StatusLoket;
use App\Events\LoketChanged;
use App\Models\Counter;
use Illuminate\Support\Facades\DB;

final class ChangeLoketStatusAction
{
    public function __construct(private readonly EnsureRegistrationCounterRemainsAction $ensureRegistration)
    {
    }

    public function execute(Counter $loket, StatusLoket $status, ?int $userId): Counter
    {
        if ($loket->is_active === $status->isActive()) {
            return $loket; // idempoten
        }

        DB::transaction(function () use ($loket, $status) {
            $this->ensureRegistration->execute($loket, $loket->tipe, $status);
            $loket->update(['is_active' => $status->isActive()]);
        });

        LoketChanged::dispatch($loket, $status->isActive() ? 'diaktifkan' : 'dinonaktifkan', $userId);

        return $loket;
    }
}
