<?php

namespace App\DTOs\Display;

readonly class SignageContentItem
{
    /**
     * @param  array<int, string>|null  $mediaUrls
     */
    public function __construct(
        public int $id,
        public string $type,
        public ?string $title,
        public ?string $body,
        public ?string $mediaUrl,
        public ?string $externalUrl,
        public ?int $durationSeconds,
        public ?array $mediaUrls = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'media_url' => $this->mediaUrl,
            'media_urls' => $this->mediaUrls,
            'external_url' => $this->externalUrl,
            'duration_seconds' => $this->durationSeconds,
        ];
    }
}
