<?php

namespace App\DTOs\Masterdata;

use App\Enums\Patient\Penjamin;

/** Data form tarif. Status tidak ikut: tarif baru selalu Aktif; status diubah lewat endpoint tersendiri. */
final readonly class TarifObatData
{
    public function __construct(
        public int $obatId,
        public Penjamin $penjamin,
        public string $satuan,
        public int $tarif,
        public string $berlakuMulai,
        public ?string $berlakuSampai,
        public ?string $sumber,
        public ?string $catatan,
    ) {
    }

    /** @param array<string, mixed> $v hasil validated() */
    public static function fromArray(array $v): self
    {
        return new self(
            obatId: (int) $v['obatId'],
            penjamin: Penjamin::from($v['penjamin']),
            satuan: trim($v['satuan']),
            tarif: (int) $v['tarif'],
            berlakuMulai: $v['berlakuMulai'],
            berlakuSampai: $v['berlakuSampai'] ?? null,
            sumber: $v['sumber'] ?? null,
            catatan: $v['catatan'] ?? null,
        );
    }
}
