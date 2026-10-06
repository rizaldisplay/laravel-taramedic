<?php

namespace App\Actions\Queue;

use App\Exceptions\Queue\CounterClaimedException;
use App\Models\Counter;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReleaseCounter
{
    public function handle(Counter $counter, User $user, bool $force = false): Counter
    {
        return DB::transaction(function () use ($counter, $user, $force): Counter {
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

            $locked->update([
                'claimed_by_user_id' => null,
                'claimed_at' => null,
            ]);

            return $locked->fresh(['claimedBy', 'services']);
        });
    }

    public function releaseAllFor(User $user): int
    {
        return Counter::query()
            ->where('claimed_by_user_id', $user->id)
            ->update([
                'claimed_by_user_id' => null,
                'claimed_at' => null,
            ]);
    }
}