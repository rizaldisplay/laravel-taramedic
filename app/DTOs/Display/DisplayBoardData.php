<?php

namespace App\DTOs\Display;

use Illuminate\Support\Collection;

readonly class DisplayBoardData
{
    /**
     * @param  Collection<int, NowServingItem>  $nowServing
     * @param  Collection<int, NowServingItem>  $recentCalls
     * @param  Collection<int, SignageContentItem>  $signageContents
     */
    public function __construct(
        public Collection $nowServing,
        public Collection $recentCalls,
        public Collection $nextQueues,
        public Collection $signageContents,
        public string $generatedAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'now_serving' => $this->nowServing->map->toArray()->values()->all(),
            'recent_calls' => $this->recentCalls->map->toArray()->values()->all(),
            'signage_contents' => $this->signageContents->map->toArray()->values()->all(),
            'generated_at' => $this->generatedAt,
        ];
    }
}
