<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueCounterUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $counterId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("counter.{$this->counterId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'counter.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'counter_id' => $this->counterId,
        ];
    }
}