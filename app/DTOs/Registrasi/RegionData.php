<?php

namespace App\DTOs\Registrasi;

final readonly class RegionData
{
    public function __construct(
        public string $code,
        public string $name,
    ) {
    }

    /** @param array{code: string|int, name: string} $row */
    public static function fromArray(array $row): self
    {
        return new self((string) $row['code'], (string) $row['name']);
    }
}
