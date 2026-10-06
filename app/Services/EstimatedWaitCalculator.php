<?php

namespace App\Services;

use App\Enums\QueueTicketPriority;
use App\Enums\QueueTicketStatus;
use App\Models\Counter;
use App\Models\QueueTicket;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EstimatedWaitCalculator
{
    public function forTicket(QueueTicket $ticket): int
    {
        $ticket->loadMissing('service');

        return $this->forService($ticket->service, $ticket);
    }

    /**
     * Calculate multiple visible tickets with a fixed number of aggregate queries.
     *
     * @param  Collection<int, QueueTicket>  $tickets
     * @return Collection<int, int>
     */
    public function forTickets(Collection $tickets): Collection
    {
        if ($tickets->isEmpty()) {
            return collect();
        }

        $tickets->each(fn (QueueTicket $ticket) => $ticket->loadMissing('service'));
        $serviceIds = $tickets->pluck('service_id')->unique()->values();

        $waitingByService = QueueTicket::query()
            ->forToday()
            ->waiting()
            ->whereIn('service_id', $serviceIds)
            ->get(['id', 'service_id', 'priority', 'skip_count', 'sequence'])
            ->groupBy('service_id');

        $inProgressByService = QueueTicket::query()
            ->forToday()
            ->whereIn('service_id', $serviceIds)
            ->whereIn('status', [QueueTicketStatus::Called, QueueTicketStatus::Serving])
            ->selectRaw('service_id, COUNT(*) as aggregate')
            ->groupBy('service_id')
            ->pluck('aggregate', 'service_id');

        $openCountersByService = Counter::query()
            ->join('counter_service', 'counter_service.counter_id', '=', 'counters.id')
            ->where('counters.is_active', true)
            ->whereIn('counter_service.service_id', $serviceIds)
            ->selectRaw('counter_service.service_id, COUNT(DISTINCT counters.id) as aggregate')
            ->groupBy('counter_service.service_id')
            ->pluck('aggregate', 'counter_service.service_id');

        return $tickets->mapWithKeys(function (QueueTicket $ticket) use ($waitingByService, $inProgressByService, $openCountersByService): array {
            $ticketsAhead = $waitingByService
                ->get($ticket->service_id, collect())
                ->filter(fn (QueueTicket $candidate): bool => $this->isAheadOf($candidate, $ticket))
                ->count();
            $inProgress = (int) ($inProgressByService[$ticket->service_id] ?? 0);
            $openCounters = max(1, (int) ($openCountersByService[$ticket->service_id] ?? 0));
            $estimatedMinutes = (int) ($ticket->service->estimated_minutes
                ?? config('queue_system.default_estimated_minutes', 5));

            return [
                $ticket->id => (int) (ceil(($ticketsAhead + $inProgress) / $openCounters) * $estimatedMinutes),
            ];
        });
    }

    public function forService(Service $service, ?QueueTicket $ticket = null): int
    {
        $estimatedMinutes = (int) ($service->estimated_minutes
            ?? config('queue_system.default_estimated_minutes', 5));

        $ticketsAhead = $this->countTicketsAhead($service, $ticket);
        $inProgress = $this->countInProgress($service);
        $openCounters = max(1, $this->countOpenCounters($service));

        $workload = $ticketsAhead + $inProgress;

        return (int) (ceil($workload / $openCounters) * $estimatedMinutes);
    }

    /**
     * Estimate wait for the next visitor who would join the end of the queue.
     */
    public function forNewTicket(Service $service, QueueTicketPriority $priority = QueueTicketPriority::Normal): int
    {
        $estimatedMinutes = (int) ($service->estimated_minutes
            ?? config('queue_system.default_estimated_minutes', 5));

        $waitingAhead = QueueTicket::query()
            ->forToday()
            ->waiting()
            ->where('service_id', $service->id)
            ->when(
                $priority === QueueTicketPriority::Priority,
                fn ($query) => $query->where('priority', QueueTicketPriority::Priority),
            )
            ->count();

        $inProgress = $this->countInProgress($service);
        $openCounters = max(1, $this->countOpenCounters($service));
        $workload = $waitingAhead + $inProgress;

        return (int) (ceil($workload / $openCounters) * $estimatedMinutes);
    }

    private function countTicketsAhead(Service $service, ?QueueTicket $ticket): int
    {
        $query = QueueTicket::query()
            ->forToday()
            ->waiting()
            ->where('service_id', $service->id);

        if ($ticket === null) {
            return $query->count();
        }

        $isPriority = $ticket->priority === QueueTicketPriority::Priority;

        return $query
            ->where(function ($outer) use ($ticket, $isPriority): void {
                if ($isPriority) {
                    $outer->where('priority', QueueTicketPriority::Priority)
                        ->where(function ($query) use ($ticket): void {
                            $this->applyOrderAhead($query, $ticket);
                        });

                    return;
                }

                $outer->where('priority', QueueTicketPriority::Priority)
                    ->orWhere(function ($query) use ($ticket): void {
                        $query->where('priority', QueueTicketPriority::Normal)
                            ->where(function ($inner) use ($ticket): void {
                                $this->applyOrderAhead($inner, $ticket);
                            });
                    });
            })
            ->count();
    }

    private function isAheadOf(QueueTicket $candidate, QueueTicket $ticket): bool
    {
        if ($ticket->priority === QueueTicketPriority::Normal && $candidate->priority === QueueTicketPriority::Priority) {
            return true;
        }

        if ($candidate->priority !== $ticket->priority) {
            return false;
        }

        return $candidate->skip_count < $ticket->skip_count
            || ($candidate->skip_count === $ticket->skip_count && $candidate->sequence < $ticket->sequence);
    }

    /**
     * @param  Builder<QueueTicket>  $query
     */
    private function applyOrderAhead($query, QueueTicket $ticket): void
    {
        $query->where('skip_count', '<', $ticket->skip_count)
            ->orWhere(function ($inner) use ($ticket): void {
                $inner->where('skip_count', $ticket->skip_count)
                    ->where('sequence', '<', $ticket->sequence);
            });
    }

    private function countInProgress(Service $service): int
    {
        return QueueTicket::query()
            ->forToday()
            ->where('service_id', $service->id)
            ->whereIn('status', [QueueTicketStatus::Called, QueueTicketStatus::Serving])
            ->count();
    }

    private function countOpenCounters(Service $service): int
    {
        return Counter::query()
            ->active()
            ->whereHas(
                'services',
                fn ($query) => $query->where('services.id', $service->id),
            )
            ->count();
    }
}
