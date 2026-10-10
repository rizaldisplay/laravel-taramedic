<?php

namespace App\DTOs\Registrasi;

use App\Enums\Patient\CaraMasuk;
use App\Enums\Patient\JenisLayanan;
use App\Enums\Patient\JenisPembayaran;
use App\Enums\Patient\Penjamin;
use App\Enums\Patient\Prioritas;

final readonly class VisitData
{
    public function __construct(
        public int $patientId,
        public int $poliId,
        public int $doctorId,
        public int $locationId,
        public JenisLayanan $jenisLayanan,
        public CaraMasuk $caraMasuk,
        public Penjamin $penjamin,
        public JenisPembayaran $jenisPembayaran,
        public Prioritas $prioritas,
        public int $counterNumber,
        public ?string $ticketId = null,
    ) {
    }

    /** @param array<string, mixed> $v hasil $request->validated() (kunci camelCase dari frontend) */
    public static function fromArray(array $v): self
    {
        return new self(
            patientId: (int) $v['patientId'],
            poliId: (int) $v['poliId'],
            doctorId: (int) $v['dokterId'],
            locationId: (int) $v['lokasiId'],
            jenisLayanan: JenisLayanan::from($v['jenisLayanan']),
            caraMasuk: CaraMasuk::from($v['caraMasuk']),
            penjamin: Penjamin::from($v['penjamin']),
            jenisPembayaran: JenisPembayaran::from($v['jenisPembayaran']),
            prioritas: Prioritas::from($v['prioritas']),
            counterNumber: (int) $v['loketId'],
            ticketId: isset($v['tiketId']) ? (string) $v['tiketId'] : null,
        );
    }
}
