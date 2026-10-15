<?php

namespace App\DTOs\Masterdata;

use App\Enums\Masterdata\Loket\StatusLoket;
use App\Enums\Masterdata\Loket\TipeLoket;

final readonly class LoketData
{
    public function __construct(
        public string $code,
        public string $nama,
        public TipeLoket $tipe,
        public ?string $deskripsi,
        public StatusLoket $status,
    ) {
    }

    /** @param array<string, mixed> $v hasil validated() */
    public static function fromArray(array $v): self
    {
        return new self(
            code: strtoupper($v['code']),
            nama: trim($v['nama']),
            tipe: TipeLoket::from($v['tipe']),
            deskripsi: $v['deskripsi'] ?? null,
            status: StatusLoket::from($v['status'] ?? 'Aktif'),
        );
    }
}
