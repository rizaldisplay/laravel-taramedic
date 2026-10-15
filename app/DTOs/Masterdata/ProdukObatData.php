<?php

namespace App\DTOs\Masterdata;

use App\Enums\Masterdata\ProdukObat\StatusFormularium;
use App\Enums\Masterdata\ProdukObat\StatusProduk;

final readonly class ProdukObatData
{
    /** @param list<array{kode: string, label: string}> $rute */
    public function __construct(
        public string $kfaCode,
        public string $kodeLokal,
        public string $nama,
        public ?string $namaGenerik,
        public string $zatAktif,
        public ?string $kekuatan,
        public ?string $satuan,
        public string $bentukSediaan,
        public array $rute,
        public ?string $produsen,
        public StatusFormularium $statusFormularium,
        public StatusProduk $status,
    ) {
    }

    /** @param array<string, mixed> $v hasil validated() */
    public static function fromArray(array $v): self
    {
        return new self(
            kfaCode: trim($v['kfaCode']),
            kodeLokal: strtoupper($v['kodeLokal']),
            nama: trim($v['nama']),
            namaGenerik: $v['namaGenerik'] ?? null,
            zatAktif: trim($v['zatAktif']),
            kekuatan: $v['kekuatan'] ?? null,
            satuan: $v['satuan'] ?? null,
            bentukSediaan: trim($v['bentukSediaan']),
            rute: array_values(array_map(
                fn (array $r) => ['kode' => strtoupper($r['kode']), 'label' => trim($r['label'])],
                $v['rute'],
            )),
            produsen: $v['produsen'] ?? null,
            statusFormularium: StatusFormularium::from($v['statusFormularium']),
            status: StatusProduk::from($v['status'] ?? 'Aktif'),
        );
    }
}
