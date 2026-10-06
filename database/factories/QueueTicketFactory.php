<?php

namespace Database\Factories;

use App\Enums\QueueTicketPriority;
use App\Enums\QueueTicketSource;
use App\Enums\QueueTicketStatus;
use App\Models\QueueTicket;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QueueTicket>
 */
class QueueTicketFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sequence = fake()->numberBetween(1, 999);
        $service = Service::factory();

        return [
            'service_id' => $service,
            'counter_id' => null,
            'ticket_number' => 'X'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT),
            'sequence' => $sequence,
            'queue_date' => today()->toDateString(),
            'status' => QueueTicketStatus::Waiting,
            'source' => QueueTicketSource::Kiosk,
            'priority' => QueueTicketPriority::Normal,
            'created_by_user_id' => null,
            'handled_by_user_id' => null,
            'issued_at' => now(),
            'called_at' => null,
            'serving_at' => null,
            'completed_at' => null,
            'recall_count' => 0,
            'skip_count' => 0,
        ];
    }

    public function waiting(): static
    {
        return $this->state(['status' => QueueTicketStatus::Waiting]);
    }

    public function called(): static
    {
        return $this->state([
            'status' => QueueTicketStatus::Called,
            'called_at' => now(),
        ]);
    }
}
