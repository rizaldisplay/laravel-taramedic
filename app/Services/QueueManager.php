<?php

namespace App\Services;

use App\Actions\Display\GetDisplayBoardData;
use App\Actions\Queue\CallNextQueueTicket;
use App\Actions\Queue\CallNextSkipQueueTicket;
use App\Actions\Queue\ClaimCounter;
use App\Actions\Queue\CompleteQueueTicket;
use App\Actions\Queue\IssueQueueTicket;
use App\Actions\Queue\MarkQueueTicketNoShow;
use App\Actions\Queue\RecallQueueTicket;
use App\Actions\Queue\ReleaseCounter;
use App\Actions\Queue\SkipQueueTicket;
use App\Actions\Queue\StartServingQueueTicket;
use App\DTOs\Display\DisplayBoardData;
use App\DTOs\Queue\CalledTicketData;
use App\DTOs\Queue\IssuedTicketData;
use App\DTOs\Queue\QueueStatisticsData;
use App\Enums\QueueTicketPriority;
use App\Enums\QueueCallQueue;
use App\Models\Antrean;
use App\Models\Counter;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

/**
 * Entry point bisnis antrian untuk Livewire / controller.
 */
class QueueManager
{
    public function __construct(
        private IssueQueueTicket $issueQueueTicket,
        private CallNextQueueTicket $callNextQueueTicket,
        private RecallQueueTicket $recallQueueTicket,
        private SkipQueueTicket $skipQueueTicket,
        private MarkQueueTicketNoShow $markQueueTicketNoShow,
        private StartServingQueueTicket $startServingQueueTicket,
        private CompleteQueueTicket $completeQueueTicket,
        private ClaimCounter $claimCounterAction,
        private ReleaseCounter $releaseCounterAction,
        private GetDisplayBoardData $getDisplayBoardData,
        private QueueStatisticsService $statisticsService,
        private QueueReportService $reportService,
    ) {}

    public function issueFromKiosk(
        Service $service,
        QueueTicketPriority $priority = QueueTicketPriority::Normal,
    ): IssuedTicketData {
        return $this->issueQueueTicket->fromKiosk($service, $priority);
    }

    public function issueFromOperator(
        Service $service,
        User $operator,
        QueueTicketPriority $priority = QueueTicketPriority::Normal,
    ): IssuedTicketData {
        return $this->issueQueueTicket->fromOperator($service, $operator, $priority);
    }

    public function callNext(
        $id,
        $loket,
        Counter $counter,
        ?User $operator = null,
        ?Service $service = null,
        QueueCallQueue $queue = QueueCallQueue::Waiting,
    ): CalledTicketData {
        return $this->callNextQueueTicket->handle($id, $loket, $counter, $operator, $service, $queue);
    }

    public function recall(
        QueueTicket $ticket,
        Counter $counter,
        $loket,
        ?User $operator = null,
    ): CalledTicketData {
        return $this->recallQueueTicket->handle($ticket, $counter, $loket, $operator);
    }

    public function claimCounter(Counter $counter, User $user, bool $force = false): Counter
    {
        return $this->claimCounterAction->handle($counter, $user, $force);
    }

    public function releaseCounter(Counter $counter, User $user, bool $force = false): Counter
    {
        return $this->releaseCounterAction->handle($counter, $user, $force);
    }

    public function releaseAllClaimsFor(User $user): int
    {
        return $this->releaseCounterAction->releaseAllFor($user);
    }

    public function defer(QueueTicket $ticket): QueueTicket
    {
        return $this->skipQueueTicket->handle($ticket);
    }

    public function markNoShow(QueueTicket $ticket): QueueTicket
    {
        return $this->markQueueTicketNoShow->handle($ticket);
    }

    public function startServing(QueueTicket $ticket, ?User $operator = null): QueueTicket
    {
        return $this->startServingQueueTicket->handle($ticket, $operator);
    }

    public function complete(QueueTicket $ticket, ?User $operator = null): QueueTicket
    {
        return $this->completeQueueTicket->handle($ticket, $operator);
    }

    public function displayBoard(): DisplayBoardData
    {
        return $this->getDisplayBoardData->handle();
    }

    public function statistics(Carbon|string|null $date = null): QueueStatisticsData
    {
        return $this->statisticsService->forDate($date);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function reportSummary(array $filters = []): QueueStatisticsData
    {
        return $this->reportService->summary($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function report(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->reportService->paginate($filters, $perPage);
    }
}
