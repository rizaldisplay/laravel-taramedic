<?php

namespace App\DTOs\Display;

readonly class NowServingItem
{
    public function __construct(
        public string $ticketNumber,
        public string $serviceName,
        public string $serviceCode,
        public string $counterName,
        public ?string $counterCode,
        public string $status,
        public ?string $calledAt = null,
        public ?int $callId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ticket_number' => $this->ticketNumber,
            'service_name' => $this->serviceName,
            'service_code' => $this->serviceCode,
            'counter_name' => $this->counterName,
            'counter_code' => $this->counterCode,
            'status' => $this->status,
            'called_at' => $this->calledAt,
            'call_id' => $this->callId,
        ];
    }
}
