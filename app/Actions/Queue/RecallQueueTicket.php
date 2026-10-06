<?php

namespace App\Actions\Queue;

use App\DTOs\Queue\CalledTicketData;
use App\Enums\QueueCallType;
use App\Enums\QueueTicketStatus;
use App\Events\QueueTicketCalled;
use App\Exceptions\Queue\CounterNotAvailableException;
use App\Exceptions\Queue\CounterServiceMismatchException;
use App\Exceptions\Queue\InvalidQueueTransitionException;
use App\Models\Counter;
use App\Models\QueueCall;
use App\Models\QueueTicket;
use App\Models\Antrean;
use App\Models\User;
use App\Services\AnnouncementService;
use App\Support\LocksQueueTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecallQueueTicket
{
    use LocksQueueTicket;

    public function __construct(
        private AnnouncementService $announcementService,
    ) {}

    public function handle(
        QueueTicket $ticket,
        Counter $counter,
        $loket,
        ?User $operator = null,
    ): CalledTicketData {

        // Log::channel('stderr')->info('Data Loket: ' . $loket);

        if (! $counter->is_active) {
            throw new CounterNotAvailableException;
        }

        if ($ticket->counter_id !== null && $ticket->counter_id !== $counter->id) {
            throw new CounterServiceMismatchException;
        }

        [$lockedTicket, $call, $shouldBroadcast] = DB::transaction(function () use ($ticket, $counter, $loket, $operator): array {
            $locked = $this->lockTicket($ticket);

            if ($locked->status !== QueueTicketStatus::Called) {
                throw new InvalidQueueTransitionException($locked->status, QueueTicketStatus::Called);
            }

            if ($locked->counter_id !== null && $locked->counter_id !== $counter->id) {
                throw new CounterServiceMismatchException;
            }

            $calledAt = now();
            $duplicateWindowSeconds = max(1, (int) config('queue_system.recall_deduplication_seconds', 2));
            $recentRecall = QueueCall::query()
                ->where('queue_ticket_id', $locked->id)
                ->where('counter_id', $counter->id)
                ->where('call_type', QueueCallType::Recall->value)
                ->where('called_at', '>=', $calledAt->copy()->subSeconds($duplicateWindowSeconds))
                ->latest('called_at')
                ->latest('id')
                ->first();

            if ($recentRecall instanceof QueueCall) {
                $locked->load(['service', 'counter']);

                return [$locked, $recentRecall, false];
            }

            $locked->update([
                'status' => QueueTicketStatus::Called,
                'counter_id' => $counter->id,
                'handled_by_user_id' => $operator?->id ?? $locked->handled_by_user_id,
                'called_at' => $calledAt,
                'recall_count' => $locked->recall_count + 1,
            ]);

            $call = QueueCall::query()->create([
                'queue_ticket_id' => $locked->id,
                'counter_id' => $counter->id,
                'antrean_id' => $loket,
                'user_id' => $operator?->id,
                'call_type' => QueueCallType::Recall,
                'called_at' => $calledAt,
            ]);

            $locked->load(['service', 'counter']);

            return [$locked, $call, true];
        });

        $announcement = $this->announcementService->build($lockedTicket, $counter, $call);
        $antrean = Antrean::find($loket);

        $result = new CalledTicketData(
            ticket: $lockedTicket,
            counter: $counter,
            call: $call,
            announcement: $announcement,
            loket: $antrean,
        );

        if ($shouldBroadcast) {
            QueueTicketCalled::dispatch($result->ticket, $result->call, $result->announcement);
        }

        return $result;
    }
}
