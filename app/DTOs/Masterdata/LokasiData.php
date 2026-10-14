<?php

namespace App\DTOs\Masterdata;

use App\Enums\Masterdata\Lokasi\StatusLokasi;
use App\Enums\Masterdata\Lokasi\TipeLokasi;

final readonly class LokasiData
{
    public function __construct(
        public string $kode,
        public string $nama,
        public TipeLokasi $tipe,
        public ?int $indukId,
        public ?int $fasyankesId,
        public ?int $poliId,
        public ?string $deskripsi,
        public StatusLokasi $status,
    ) {
    }

    /** @param array<string, mixed> $v hasil validated() */
    public static function fromArray(array $v): self
    {
        return new self(
            kode: strtoupper($v['kode']),
            nama: trim($v['nama']),
            tipe: TipeLokasi::from($v['tipe']),
            indukId: isset($v['indukId']) ? (int) $v['indukId'] : null,
            fasyankesId: isset($v['fasyankesId']) ? (int) $v['fasyankesId'] : null,
            poliId: isset($v['poliId']) ? (int) $v['poliId'] : null,
            deskripsi: $v['deskripsi'] ?? null,
            status: StatusLokasi::from($v['status'] ?? 'Aktif'),
        );
    }
}
