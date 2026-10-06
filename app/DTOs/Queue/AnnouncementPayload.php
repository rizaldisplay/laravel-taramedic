<?php

namespace App\DTOs\Queue;

readonly class AnnouncementPayload
{
    public function __construct(
        public string $message,
        public string $ticketNumber,
        public string $serviceName,
        public string $serviceCode,
        public string $counterName,
        public string $counterCode,
        public int $callId,
        public string $calledAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'ticket_number' => $this->ticketNumber,
            'service_name' => $this->serviceName,
            'service_code' => $this->serviceCode,
            'counter_name' => $this->counterName,
            'counter_code' => $this->counterCode,
            'call_id' => $this->callId,
            'called_at' => $this->calledAt,
        ];
    }
}
