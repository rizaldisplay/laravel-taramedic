<?php

namespace App\Services;

use App\DTOs\Queue\QueueStatisticsData;
use App\Enums\QueueTicketStatus;
use App\Models\QueueTicket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class QueueReportService
{
    /**
     * @param  array{
     *     date_from?: Carbon|string|null,
     *     date_to?: Carbon|string|null,
     *     service_id?: int|null,
     *     counter_id?: int|null,
     *     status?: QueueTicketStatus|null,
     *     source?: string|null,
     * }  $filters
     */
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->query($filters)
            ->with(['service:id,name,code', 'counter:id,name,code', 'createdBy:id,name', 'handledBy:id,name'])
            ->latest('issued_at')
            ->paginate($perPage);
    }

    /**
     * @param  array{
     *     date_from?: Carbon|string|null,
     *     date_to?: Carbon|string|null,
     *     service_id?: int|null,
     *     counter_id?: int|null,
     *     status?: QueueTicketStatus|null,
     *     source?: string|null,
     * }  $filters
     */
    public function summary(array $filters = []): QueueStatisticsData
    {
        $query = $this->query($filters);

        $statusCounts = (clone $query)
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $byStatus = collect(QueueTicketStatus::cases())
            ->mapWithKeys(fn (QueueTicketStatus $status): array => [
                $status->value => (int) ($statusCounts[$status->value] ?? 0),
            ])
            ->all();

        return new QueueStatisticsData(
            total: array_sum($byStatus),
            waiting: $byStatus[QueueTicketStatus::Waiting->value] ?? 0,
            called: $byStatus[QueueTicketStatus::Called->value] ?? 0,
            serving: $byStatus[QueueTicketStatus::Serving->value] ?? 0,
            completed: $byStatus[QueueTicketStatus::Completed->value] ?? 0,
            noShow: $byStatus[QueueTicketStatus::NoShow->value] ?? 0,
            averageWaitMinutes: $this->averageWaitMinutes($query),
            byStatus: $byStatus,
            byService: [],
            byCounter: [],
        );
    }

    /**
     * @param  array{
     *     date_from?: Carbon|string|null,
     *     date_to?: Carbon|string|null,
     *     service_id?: int|null,
     *     counter_id?: int|null,
     *     status?: QueueTicketStatus|null,
     *     source?: string|null,
     * }  $filters
     * @return Builder<QueueTicket>
     */
    public function query(array $filters = []): Builder
    {
        $query = QueueTicket::query();

        if (! empty($filters['date_from'])) {
            $query->where('queue_date', '>=', Carbon::parse($filters['date_from'])->toDateString());
        }

        if (! empty($filters['date_to'])) {
            $query->where('queue_date', '<=', Carbon::parse($filters['date_to'])->toDateString());
        }

        if (! empty($filters['service_id'])) {
            $query->where('service_id', $filters['service_id']);
        }

        if (! empty($filters['counter_id'])) {
            $query->where('counter_id', $filters['counter_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        return $query;
    }

    private function averageWaitMinutes(Builder $query): ?float
    {
        $averageExpression = match (DB::connection()->getDriverName()) {
            'sqlite' => 'AVG((julianday(called_at) - julianday(issued_at)) * 1440)',
            'pgsql' => 'AVG(EXTRACT(EPOCH FROM (called_at - issued_at)) / 60)',
            default => 'AVG(TIMESTAMPDIFF(SECOND, issued_at, called_at) / 60)',
        };

        $average = (clone $query)
            ->whereNotNull('issued_at')
            ->whereNotNull('called_at')
            ->where('status', QueueTicketStatus::Completed)
            ->value(DB::raw($averageExpression));

        if ($average === null) {
            return null;
        }

        return round((float) $average, 1);
    }
}
