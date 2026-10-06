<?php

namespace App\Actions\Queue;

use App\Exceptions\Queue\CounterClaimedException;
use App\Exceptions\Queue\CounterNotAvailableException;
use App\Models\Counter;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ClaimCounter
{
    public function handle(Counter $counter, User $user, bool $force = false): Counter
    {
        if (! $counter->is_active) {
            throw new CounterNotAvailableException;
        }

        return DB::transaction(function () use ($counter, $user, $force): Counter {
            User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $locked = Counter::query()
                ->whereKey($counter->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $locked->claimed_by_user_id !== null
                && $locked->claimed_by_user_id !== $user->id
                && ! $force
            ) {
                $locked->loadMissing('claimedBy');

                throw new CounterClaimedException($locked->claimedBy?->name ?? 'petugas lain');
            }

            // One active claim per operator.
            Counter::query()
                ->where('claimed_by_user_id', $user->id)
                ->whereKeyNot($locked->id)
                ->update([
                    'claimed_by_user_id' => null,
                    'claimed_at' => null,
                ]);

            $locked->update([
                'claimed_by_user_id' => $user->id,
                'claimed_at' => now(),
            ]);

            return $locked->fresh(['claimedBy', 'services']);
        });
    }
}
