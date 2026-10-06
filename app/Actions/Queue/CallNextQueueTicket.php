<?php

namespace App\Actions\Queue;

use App\DTOs\Queue\CalledTicketData;
use App\Enums\QueueCallType;
use App\Enums\QueueCallQueue;
use App\Enums\QueueTicketStatus;
use App\Events\QueueTicketCalled;
use App\Exceptions\Queue\CounterHasActiveTicketException;
use App\Exceptions\Queue\CounterNotAvailableException;
use App\Exceptions\Queue\CounterServiceMismatchException;
use App\Exceptions\Queue\NoWaitingTicketException;
use App\Models\Antrean;
use App\Models\Counter;
use App\Models\QueueCall;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\AnnouncementService;
use App\Support\QueueTransitionValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CallNextQueueTicket
{
    public function __construct(
        private AnnouncementService $announcementService,
        private QueueTransitionValidator $transitionValidator,
    ) {}

    public function handle(
        $id,
        $loket,
        Counter $counter,
        ?User $operator = null,
        ?Service $service = null,
        QueueCallQueue $queue = QueueCallQueue::Waiting,
    ): CalledTicketData {
        [$ticket, $call, $lockedCounter, $lockedAntrean] = DB::transaction(function () use ($id, $loket, $counter, $operator, $service, $queue): array {

            // Log::channel('stderr')->info('Data Loket: ' . $loket);

            /* mengunci input loket secara bersamaan */
            $lockedAntrean = Antrean::query()
                ->lockForUpdate()
                ->findOrFail($loket);

            if (! $lockedAntrean->is_active) {
                throw new CounterNotAvailableException;
            }

            $lockedCounter = Counter::query()
                ->lockForUpdate()
                ->findOrFail($counter->id);

            if (! $lockedCounter->is_active) {
                throw new CounterNotAvailableException;
            }

            $serviceIds = $lockedCounter->services()->pluck('services.id');

            if ($service !== null) {
                if (! $serviceIds->contains($service->id)) {
                    throw new CounterServiceMismatchException;
                }

                $serviceIds = collect([$service->id]);
            }

            if ($serviceIds->isEmpty()) {
                throw new CounterServiceMismatchException;
            }

            $hasActive = QueueTicket::query()
                ->forToday()
                ->when($id, function ($query, $id) {
                        return $query->where('id', $id);
                    })
                ->where('counter_id', $lockedCounter->id)
                ->where('antrean_id', $lockedAntrean->id)
                ->whereIn('status', [QueueTicketStatus::Called])
                ->exists();

            if ($hasActive) {
                throw new CounterHasActiveTicketException;
            }

            $ticketQuery = QueueTicket::query()
                ->forToday()
                ->when($id, function ($query, $id) {
                    return $query->where('id', $id);
                })
                ->whereIn('service_id', $serviceIds)
                ->lockForUpdate();

            if ($queue === QueueCallQueue::Skip) {
                $ticketQuery
                    ->waiting()
                    ->where('skip_count', '>', 0);
            } else {
                $ticketQuery
                    ->waiting()
                    ->where('skip_count', 0);
            }

            $ticket = $ticketQuery
                ->orderedForCall()
                ->first();

            if ($ticket === null) {
                throw new NoWaitingTicketException;
            }

            $this->transitionValidator->assertCanTransition($ticket, QueueTicketStatus::Called);

            $calledAt = now();

            $ticket->update([
                'antrean_id' => $lockedAntrean->id,
                'status' => QueueTicketStatus::Called,
                'counter_id' => $lockedCounter->id,
                'handled_by_user_id' => $operator?->id,
                'called_at' => $calledAt,
            ]);

            $call = QueueCall::query()->create([
                'queue_ticket_id' => $ticket->id,
                'antrean_id' => $lockedAntrean->id,
                'counter_id' => $lockedCounter->id,
                'user_id' => $operator?->id,
                'call_type' => QueueCallType::Initial,
                'called_at' => $calledAt,
            ]);

            $ticket->load(['service', 'counter']);

            return [$ticket, $call, $lockedCounter, $lockedAntrean];
        });

        $announcement = $this->announcementService->build($ticket, $lockedCounter, $call);

        $result = new CalledTicketData(
            ticket: $ticket,
            loket: $lockedAntrean,
            counter: $lockedCounter,
            call: $call,
            announcement: $announcement,
        );

        QueueTicketCalled::dispatch($result->ticket, $result->call, $result->announcement);

        return $result;
    }
}
