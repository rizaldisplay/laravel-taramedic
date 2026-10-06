<?php

namespace App\Policies;

use App\Enums\QueueTicketStatus;
use App\Models\Counter;
use App\Models\QueueTicket;
use App\Models\User;

class QueueTicketPolicy
{
    /**
     * Whether the operator may perform desk actions on this ticket.
     */
    public function manage(User $user, QueueTicket $queueTicket): bool
    {
        if ($user->role->canAccessAdmin()) {
            return true;
        }

        if (! $user->role->canAccessOperator()) {
            return false;
        }

        if (
            ! $queueTicket->queue_date?->isSameDay(today())
            || ! in_array($queueTicket->status, [QueueTicketStatus::Called, QueueTicketStatus::Serving], true)
        ) {
            return false;
        }

        if ($queueTicket->counter_id !== null) {
            return Counter::query()
                ->whereKey($queueTicket->counter_id)
                ->where('claimed_by_user_id', $user->id)
                ->exists();
        }

        return Counter::query()
            ->where('claimed_by_user_id', $user->id)
            ->whereHas(
                'services',
                fn ($query) => $query->where('services.id', $queueTicket->service_id),
            )
            ->exists();
    }
}
