<?php

namespace App\DTOs\Display;

readonly class DisplayCounterData
{
    /**
     * @param array<string, mixed>|null $antreanSaatIni
     * @param array<int, array<string, mixed>> $daftarAntrean
     */
    public function __construct(
        public int $menunggu,
        public int $terlewati,
        public ?array $antreanSaatIni,
        public array $daftarAntrean,
        public array $daftarTerlewati,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'menunggu' => $this->menunggu,
            'terlewati' => $this->terlewati,
            'antreanSaatIni' => $this->antreanSaatIni,
            'daftarAntrean' => $this->daftarAntrean,
            'daftarTerlewati' => $this->daftarTerlewati,
        ];
    }
}