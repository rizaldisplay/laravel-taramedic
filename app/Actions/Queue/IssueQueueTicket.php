<?php

namespace App\Actions\Queue;

use App\DTOs\Queue\IssuedTicketData;
use App\Enums\QueueTicketPriority;
use App\Enums\QueueTicketSource;
use App\Enums\QueueTicketStatus;
use App\Events\QueueTicketIssued;
use App\Exceptions\Queue\ServiceNotAvailableException;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\EstimatedWaitCalculator;
use App\Support\QueueNumberGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class IssueQueueTicket
{
    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private QueueNumberGenerator $numberGenerator,
        private EstimatedWaitCalculator $waitCalculator,
    ) {}

    public function fromKiosk(
        Service $service,
        QueueTicketPriority $priority = QueueTicketPriority::Normal,
    ): IssuedTicketData {
        // Priority is operator-only; ignore any client-supplied priority on kiosk.
        return $this->issue(
            service: $service,
            source: QueueTicketSource::Kiosk,
            priority: QueueTicketPriority::Normal,
        );
    }

    public function fromOperator(
        Service $service,
        User $operator,
        QueueTicketPriority $priority = QueueTicketPriority::Normal,
    ): IssuedTicketData {
        return $this->issue(
            service: $service,
            source: QueueTicketSource::Operator,
            priority: $priority,
            operator: $operator,
        );
    }

    private function issue(
        Service $service,
        QueueTicketSource $source,
        QueueTicketPriority $priority,
        ?User $operator = null,
    ): IssuedTicketData {
        if (! $service->is_active) {
            throw new ServiceNotAvailableException;
        }

        $attempt = 0;
        $ticket = null;

        while ($ticket === null) {
            $attempt++;

            try {
                $ticket = DB::transaction(function () use ($service, $source, $priority, $operator): QueueTicket {
                    $lockedService = Service::query()
                        ->whereKey($service->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (! $lockedService->is_active) {
                        throw new ServiceNotAvailableException;
                    }

                    $sequence = $this->numberGenerator->nextSequence($lockedService);
                    $queueDate = today()->toDateString();

                    return QueueTicket::query()->create([
                        'service_id' => $lockedService->id,
                        'ticket_number' => $this->numberGenerator->format($lockedService, $sequence),
                        'sequence' => $sequence,
                        'queue_date' => $queueDate,
                        'status' => QueueTicketStatus::Waiting,
                        'source' => $source,
                        'priority' => $priority,
                        'created_by_user_id' => $operator?->id,
                        'issued_at' => now(),
                    ]);
                }, attempts: self::MAX_ATTEMPTS);
            } catch (ServiceNotAvailableException $exception) {
                throw $exception;
            } catch (QueryException $exception) {
                if ($attempt >= self::MAX_ATTEMPTS || ! $this->isUniqueConstraintViolation($exception)) {
                    throw $exception;
                }
            }
        }

        $ticket->load('service');

        $waitingCount = QueueTicket::query()
            ->forToday()
            ->waiting()
            ->where('service_id', $service->id)
            ->count();

        $result = new IssuedTicketData(
            ticket: $ticket,
            estimatedWaitMinutes: $this->waitCalculator->forTicket($ticket),
            waitingCount: $waitingCount,
        );

        QueueTicketIssued::dispatch($ticket);

        return $result;
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);

        // SQLSTATE 23000 = integrity constraint; MySQL 1062 / SQLite 19 / Postgres 23505
        return $sqlState === '23000'
            || $sqlState === '23505'
            || $driverCode === 1062
            || $driverCode === 19
            || str_contains(strtolower($exception->getMessage()), 'unique');
    }
}
