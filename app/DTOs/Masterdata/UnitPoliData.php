<?php

namespace App\DTOs\Masterdata;

use App\Enums\Masterdata\UnitPoli\JenisUnit;
use App\Enums\Masterdata\UnitPoli\StatusUnit;

final readonly class UnitPoliData
{
    public function __construct(
        public string $kode,
        public string $nama,
        public JenisUnit $jenis,
        public ?int $fasyankesId,
        public ?string $deskripsi,
        public bool $untukAntrean,
        public StatusUnit $status,
    ) {
    }

    /** @param array<string, mixed> $v hasil validated() */
    public static function fromArray(array $v): self
    {
        return new self(
            kode: strtoupper($v['kode']),
            nama: trim($v['nama']),
            jenis: JenisUnit::from($v['jenis']),
            fasyankesId: isset($v['fasyankesId']) ? (int) $v['fasyankesId'] : null,
            deskripsi: $v['deskripsi'] ?? null,
            untukAntrean: (bool) $v['untukAntrean'],
            status: StatusUnit::from($v['status'] ?? 'Aktif'),
        );
    }

    /** @return array<string, mixed> kolom tabel polis */
    public function toAttributes(): array
    {
        return [
            'kode' => $this->kode,
            'name' => $this->nama,
            'jenis' => $this->jenis,
            'facility_id' => $this->fasyankesId,
            'deskripsi' => $this->deskripsi,
            'untuk_antrean' => $this->untukAntrean,
            'is_active' => $this->status->isActive(),
        ];
    }
}
