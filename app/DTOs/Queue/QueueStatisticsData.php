<?php

namespace App\DTOs\Queue;

readonly class QueueStatisticsData
{
    /**
     * @param  array<string, int>  $byStatus
     * @param  array<int, array<string, mixed>>  $byService
     * @param  array<int, array<string, mixed>>  $byCounter
     */
    public function __construct(
        public int $total,
        public int $waiting,
        public int $called,
        public int $serving,
        public int $completed,
        public int $noShow,
        public ?float $averageWaitMinutes,
        public array $byStatus,
        public array $byService,
        public array $byCounter,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'waiting' => $this->waiting,
            'called' => $this->called,
            'serving' => $this->serving,
            'completed' => $this->completed,
            'no_show' => $this->noShow,
            'average_wait_minutes' => $this->averageWaitMinutes,
            'by_status' => $this->byStatus,
            'by_service' => $this->byService,
            'by_counter' => $this->byCounter,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            total: (int) ($data['total'] ?? 0),
            waiting: (int) ($data['waiting'] ?? 0),
            called: (int) ($data['called'] ?? 0),
            serving: (int) ($data['serving'] ?? 0),
            completed: (int) ($data['completed'] ?? 0),
            noShow: (int) ($data['no_show'] ?? 0),
            averageWaitMinutes: isset($data['average_wait_minutes']) ? (float) $data['average_wait_minutes'] : null,
            byStatus: is_array($data['by_status'] ?? null) ? $data['by_status'] : [],
            byService: is_array($data['by_service'] ?? null) ? $data['by_service'] : [],
            byCounter: is_array($data['by_counter'] ?? null) ? $data['by_counter'] : [],
        );
    }
}
