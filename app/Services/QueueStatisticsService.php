<?php

namespace App\Services;

use App\DTOs\Queue\QueueStatisticsData;
use App\Enums\QueueTicketStatus;
use App\Models\QueueTicket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class QueueStatisticsService
{
    public function forDate(Carbon|string|null $date = null): QueueStatisticsData
    {
        $queueDate = Carbon::parse($date ?? today())->toDateString();
        $cacheKey = $this->cacheKey($queueDate);

        // Laravel 13 rejects arbitrary object unserialization; purge legacy DTO payloads.
        $cached = Cache::get($cacheKey);
        if ($cached !== null && ! is_array($cached)) {
            Cache::forget($cacheKey);
        }

        /** @var array<string, mixed> $payload */
        $payload = Cache::flexible(
            $cacheKey,
            [5, 20],
            fn (): array => $this->computeForDate($queueDate)->toArray(),
        );

        if (! is_array($payload)) {
            Cache::forget($cacheKey);

            return $this->computeForDate($queueDate);
        }

        return QueueStatisticsData::fromArray($payload);
    }

    public function forgetCache(Carbon|string|null $date = null): void
    {
        $queueDate = Carbon::parse($date ?? today())->toDateString();
        Cache::forget($this->cacheKey($queueDate));
    }

    private function cacheKey(string $queueDate): string
    {
        return "queue.stats.{$queueDate}";
    }

    private function computeForDate(string $queueDate): QueueStatisticsData
    {
        $statusCounts = QueueTicket::query()
            ->where('queue_date', $queueDate)
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $byStatus = collect(QueueTicketStatus::cases())
            ->mapWithKeys(fn (QueueTicketStatus $status): array => [
                $status->value => (int) ($statusCounts[$status->value] ?? 0),
            ])
            ->all();

        $total = array_sum($byStatus);

        $averageWaitMinutes = $this->averageWaitMinutes($queueDate);

        return new QueueStatisticsData(
            total: $total,
            waiting: $byStatus[QueueTicketStatus::Waiting->value] ?? 0,
            called: $byStatus[QueueTicketStatus::Called->value] ?? 0,
            serving: $byStatus[QueueTicketStatus::Serving->value] ?? 0,
            completed: $byStatus[QueueTicketStatus::Completed->value] ?? 0,
            noShow: $byStatus[QueueTicketStatus::NoShow->value] ?? 0,
            averageWaitMinutes: $averageWaitMinutes,
            byStatus: $byStatus,
            byService: $this->groupByService($queueDate),
            byCounter: $this->groupByCounter($queueDate),
        );
    }

    private function averageWaitMinutes(string $queueDate): ?float
    {
        $query = QueueTicket::query()
            ->where('queue_date', $queueDate)
            ->whereNotNull('issued_at')
            ->whereNotNull('called_at')
            ->where('status', QueueTicketStatus::Completed);

        $averageExpression = match (DB::connection()->getDriverName()) {
            'sqlite' => 'AVG((julianday(called_at) - julianday(issued_at)) * 1440)',
            'pgsql' => 'AVG(EXTRACT(EPOCH FROM (called_at - issued_at)) / 60)',
            default => 'AVG(TIMESTAMPDIFF(SECOND, issued_at, called_at) / 60)',
        };

        $average = $query->value(DB::raw($averageExpression));

        if ($average === null) {
            return null;
        }

        return round((float) $average, 1);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function groupByService(string $queueDate): array
    {
        return QueueTicket::query()
            ->where('queue_tickets.queue_date', $queueDate)
            ->join('services', 'services.id', '=', 'queue_tickets.service_id')
            ->select([
                'queue_tickets.service_id',
                'services.name as service_name',
                'services.code as service_code',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN queue_tickets.status = 'waiting' THEN 1 ELSE 0 END) as waiting"),
                DB::raw("SUM(CASE WHEN queue_tickets.status = 'completed' THEN 1 ELSE 0 END) as completed"),
            ])
            ->groupBy('queue_tickets.service_id', 'services.name', 'services.code')
            ->orderBy('services.name')
            ->get()
            ->map(fn ($row): array => [
                'service_id' => (int) $row->service_id,
                'service_name' => $row->service_name,
                'service_code' => $row->service_code,
                'total' => (int) $row->total,
                'waiting' => (int) $row->waiting,
                'completed' => (int) $row->completed,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function groupByCounter(string $queueDate): array
    {
        return QueueTicket::query()
            ->where('queue_tickets.queue_date', $queueDate)
            ->whereNotNull('queue_tickets.counter_id')
            ->join('counters', 'counters.id', '=', 'queue_tickets.counter_id')
            ->select([
                'queue_tickets.counter_id',
                'counters.name as counter_name',
                'counters.code as counter_code',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN queue_tickets.status = 'serving' THEN 1 ELSE 0 END) as serving"),
                DB::raw("SUM(CASE WHEN queue_tickets.status = 'completed' THEN 1 ELSE 0 END) as completed"),
            ])
            ->groupBy('queue_tickets.counter_id', 'counters.name', 'counters.code')
            ->orderBy('counters.name')
            ->get()
            ->map(fn ($row): array => [
                'counter_id' => (int) $row->counter_id,
                'counter_name' => $row->counter_name,
                'counter_code' => $row->counter_code,
                'total' => (int) $row->total,
                'serving' => (int) $row->serving,
                'completed' => (int) $row->completed,
            ])
            ->all();
    }
}
